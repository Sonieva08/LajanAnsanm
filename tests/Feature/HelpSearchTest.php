<?php

namespace Tests\Feature;

use App\Support\AiAnswerer;
use App\Support\HelpCenter;
use App\Support\HelpSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_accents_and_small_typos_are_tolerated(): void
    {
        $hit = HelpSearch::best('Kouman pou m konfime mwen resevva kob mwen', HelpCenter::for('member'));

        $this->assertSame('received', $hit['id'] ?? null);
    }

    public function test_french_and_creole_synonyms_find_prepared_answers(): void
    {
        $hit = HelpSearch::best('comment payer abonnement', HelpCenter::for('creator'));

        $this->assertSame('plans', $hit['id'] ?? null);
    }

    public function test_nonsense_does_not_match_an_unrelated_faq(): void
    {
        $this->assertNull(HelpSearch::best('xyzqwe blabla', HelpCenter::for('creator')));
        $this->assertNull(HelpSearch::best('?!', HelpCenter::for('creator')));
    }

    public function test_ask_endpoint_keeps_legacy_widget_fields_and_adds_source_fields(): void
    {
        $this->postJson(route('help.ask'), ['audience' => 'member', 'query' => 'resevva kob'])
            ->assertOk()
            ->assertJsonPath('source', 'faq')
            ->assertJsonPath('found', true)
            ->assertJsonPath('matched_id', 'received')
            ->assertJsonPath('id', 'received')
            ->assertJsonStructure(['text', 'answer', 'actions', 'related']);
    }

    public function test_custom_ai_is_used_only_after_no_faq_matches(): void
    {
        $this->app->bind(AiAnswerer::class, fn () => new class implements AiAnswerer
        {
            public function answer(string $question, array $faqs): ?string
            {
                return 'Repons IA tès.';
            }
        });

        $this->postJson(route('help.ask'), ['audience' => 'member', 'query' => 'xyzqwe blabla'])
            ->assertOk()
            ->assertJsonPath('source', 'ai')
            ->assertJsonPath('answer.0', 'Repons IA tès.');

        $this->postJson(route('help.ask'), ['audience' => 'member', 'query' => 'resevva kob'])
            ->assertJsonPath('source', 'faq');
    }

    public function test_guest_and_admin_audiences_remain_supported(): void
    {
        $this->postJson(route('help.ask'), ['audience' => 'guest', 'query' => 'kisa lajanansanm ye'])
            ->assertOk()
            ->assertJsonPath('matched_id', 'what_is');

        $this->postJson(route('help.ask'), ['audience' => 'admin', 'query' => 'comment gérer un utilisateur'])
            ->assertOk();
    }
}
