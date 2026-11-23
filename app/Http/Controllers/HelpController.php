<?php

namespace App\Http\Controllers;

use App\Models\HelpEvent;
use App\Support\AiAnswerer;
use App\Support\HelpAssistant;
use App\Support\HelpCenter;
use App\Support\HelpSearch;
use App\Support\NullAnswerer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpController extends Controller
{
    // Enregistre une ouverture, une recherche ou une question sans réponse (aucune identité, texte nettoyé)
    public function log(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audience' => ['required', 'in:creator,member,guest,admin'],
            'type' => ['required', 'in:open,search,miss'],
            'faq_id' => ['nullable', 'string', 'max:40'],
            'query' => ['nullable', 'string', 'max:300'],
        ]);

        $faq = HelpCenter::find($data['audience'], $data['faq_id'] ?? null);
        $query = $data['type'] === 'open' && ! $faq ? null : HelpEvent::clean($data['query'] ?? null);

        HelpEvent::create([
            'audience' => $data['audience'],
            'type' => $data['type'],
            'faq_id' => $faq['id'] ?? null,
            'query' => $query,
        ]);

        return response()->json([], 204);
    }

    // Traitement intelligent des questions par l'assistant IA autonome local
    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audience' => ['required', 'in:creator,member,guest,admin'],
            'query' => ['required', 'string', 'max:300'],
        ]);

        $query = $data['query'];
        $audience = $data['audience'];
        $faqs = HelpCenter::for($audience);
        $local = HelpAssistant::answer($query, $audience, $request->user());

        if (! empty($local['matched_id']) || ! empty($local['is_personalized'])) {
            $this->recordAnswer($audience, $local['matched_id'] ?? null, $query);

            return response()->json($this->withSource($local, ! empty($local['matched_id']) ? 'faq' : 'ai', true));
        }

        if ($hit = HelpSearch::best($query, $faqs)) {
            $this->recordAnswer($audience, $hit['id'], $query);
            $answer = [
                'text' => $hit['a'],
                'actions' => $hit['actions'] ?? [],
                'related' => $hit['related'] ?? [],
                'tone' => $hit['tone'] ?? 'normal',
                'is_personalized' => false,
                'matched_id' => $hit['id'],
                'id' => $hit['id'],
                'question' => $hit['q'],
            ];

            return response()->json($this->withSource($answer, 'faq', true));
        }

        $answerer = app()->bound(AiAnswerer::class) ? app(AiAnswerer::class) : new NullAnswerer;
        $aiAnswer = $answerer->answer($query, $faqs);
        if (is_string($aiAnswer) && trim($aiAnswer) !== '') {
            $answer = [
                'text' => [mb_substr(trim($aiAnswer), 0, 2000)],
                'actions' => [],
                'related' => [],
                'tone' => 'normal',
                'is_personalized' => false,
                'matched_id' => null,
            ];
            $this->recordAnswer($audience, null, null);

            return response()->json($this->withSource($answer, 'ai', true));
        }

        $this->recordAnswer($audience, null, $query);

        return response()->json($this->withSource($local, 'none', false));
    }

    private function recordAnswer(string $audience, ?string $faqId, ?string $query): void
    {
        HelpEvent::create([
            'audience' => $audience,
            'type' => $faqId === null ? ($query === null ? 'search' : 'miss') : 'search',
            'faq_id' => $faqId,
            'query' => $query === null ? null : HelpEvent::clean($query),
        ]);
    }

    private function withSource(array $answer, string $source, bool $found): array
    {
        $answer['found'] = $found;
        $answer['source'] = $source;
        $answer['answer'] = $answer['text'] ?? [];
        $answer['actions'] ??= [];
        $answer['related'] ??= [];

        return $answer;
    }
}
