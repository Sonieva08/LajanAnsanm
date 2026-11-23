<?php

namespace Tests\Feature;

use App\Models\HelpEvent;
use App\Models\User;
use App\Support\HelpCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['is_owner' => true])->save();

        return $u;
    }

    /* ---------- Catalogue ---------- */

    public function test_every_answer_is_complete_and_consistent(): void
    {
        foreach (['creator', 'member', 'guest', 'admin'] as $audience) {
            $items = HelpCenter::for($audience);
            $ids = array_column($items, 'id');

            $this->assertGreaterThanOrEqual(8, count($items), $audience);
            $this->assertSame($ids, array_values(array_unique($ids)), 'identifiants uniques');

            foreach ($items as $i) {
                $this->assertNotSame('', trim($i['q']), $i['id']);
                $this->assertNotEmpty($i['a'], $i['id']);
                $this->assertLessThanOrEqual(3, count($i['a']), "{$i['id']} : réponse trop longue, découpez ou simplifiez");
                $this->assertNotSame('', $i['icon']);
                foreach ($i['related'] as $rel) {
                    $this->assertContains($rel, $ids, "{$i['id']} renvoie vers un identifiant inconnu : {$rel}");
                }
                foreach ($i['actions'] as [$label, $url]) {
                    $this->assertNotSame('', $label);
                    $this->assertStringStartsWith('http', $url);
                }
            }
        }
    }

    public function test_the_help_never_claims_things_the_app_does_not_do(): void
    {
        $text = mb_strtolower(json_encode([HelpCenter::for('creator'), HelpCenter::for('member'), HelpCenter::for('guest'), HelpCenter::for('admin')], JSON_UNESCAPED_UNICODE));

        foreach (['24/7', 'bout-an-bout', 'intelijans atifisyèl', 'chifreman', 'otomatikman voye sms'] as $claim) {
            $this->assertStringNotContainsString($claim, $text);
        }
    }

    public function test_the_member_help_never_says_the_platform_holds_the_money(): void
    {
        $portal = collect(HelpCenter::for('creator'))->firstWhere('id', 'portal');

        $this->assertStringContainsString('LajanAnsanm pa jwenn lajan an', implode(' ', $portal['a']));
    }

    public function test_the_assistant_explains_tontines_and_lajanansanm_in_natural_language(): void
    {
        $questions = [
            ['creator', 'Kisa tontin nan ye egzateman?', 'tontine_basics', 'gwoup moun'],
            ['creator', 'Kisa yon tontine ye exactement ?', 'tontine_basics', 'kontribye'],
            ['member', 'C’est quoi une tontine ?', 'tontine_basics', 'chak tou'],
            ['guest', 'Kisa Lajan ansanm ye egzateman?', 'what_is', 'aplikasyon entènèt'],
            ['creator', 'Kisa lajanasnanm ye?', 'what_is', 'pa resevwa ni kenbe lajan'],
        ];

        foreach ($questions as [$audience, $query, $faqId, $expectedText]) {
            $response = $this->postJson(route('help.ask'), [
                'audience' => $audience,
                'query' => $query,
            ])->assertOk();

            $this->assertSame($faqId, $response->json('matched_id'), $query);
            $this->assertStringContainsString($expectedText, mb_strtolower(implode(' ', $response->json('text'))), $query);
        }
    }

    public function test_help_answers_member_contribution_and_payout_scenarios(): void
    {
        $questions = [
            ['member', 'Kijan pou yon manm bay kontribisyon pa l?', 'contribution_amount', 'montan pou peye a'],
            ['member', 'Mwen voye deklarasyon an men kontribisyon an poko konte. Poukisa?', 'declaration_pending', 'poko konte kòm kontribisyon'],
            ['creator', 'Kijan pou m vèse lajan an bay benefisyè a?', 'payout', 'omwen yon kontribisyon'],
            ['creator', 'Benefisyè a di li pa resevwa lajan an, kisa pou m fè?', 'payout_problem', 'verifye detay tranzaksyon an'],
        ];

        foreach ($questions as [$audience, $query, $faqId, $expectedText]) {
            $response = $this->postJson(route('help.ask'), [
                'audience' => $audience,
                'query' => $query,
            ])->assertOk();

            $this->assertSame($faqId, $response->json('matched_id'), $query);
            $this->assertStringContainsString($expectedText, mb_strtolower(implode(' ', $response->json('text'))), $query);
        }

        $creatorFaqs = collect(HelpCenter::for('creator'))->keyBy('id');
        $memberFaqs = collect(HelpCenter::for('member'))->keyBy('id');
        $this->assertArrayHasKey('payout_proof', $creatorFaqs);
        $this->assertArrayHasKey('payout_problem', $creatorFaqs);
        $this->assertArrayHasKey('contribution_amount', $memberFaqs);
        $this->assertArrayHasKey('declaration_pending', $memberFaqs);
    }

    /* ---------- Affichage ---------- */

    public function test_the_help_button_appears_across_all_interfaces(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('data-help-widget', false)
            ->assertSee('Kisa LajanAnsanm ye?');

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertSee('data-help-widget', false)
            ->assertSee('Ki jan pou m ajoute yon manm?');

        $this->actingAs($this->owner())->get(route('admin.overview'))->assertOk()
            ->assertSee('data-help-widget', false)
            ->assertSee('Kijan pou m valide yon peman abònman?');
    }

    public function test_the_member_portal_layout_includes_the_member_help(): void
    {
        $file = file_get_contents(resource_path('views/components/portal-layout.blade.php'));

        $this->assertStringContainsString("partials.help-widget", $file);
        $this->assertStringContainsString("'audience' => 'member'", $file);
    }

    public function test_the_embedded_help_data_is_valid_json(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/dashboard')->getContent();

        $this->assertSame(1, preg_match('#<script type="application/json" id="help-data">(.*?)</script>#s', $html, $m));
        $data = json_decode($m[1], true);

        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame('creator', $data['audience']);
        $this->assertNotEmpty($data['faqs']);
        $this->assertSame(route('help.log'), $data['logUrl']);
    }

    public function test_the_chat_and_message_thread_have_a_constrained_scrollable_layout(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/dashboard')->getContent();

        $this->assertStringContainsString('h-[88vh] max-h-[88vh]', $html);
        $this->assertStringContainsString('id="help-thread" class="min-h-0 overflow-y-auto overscroll-contain', $html);
    }

    public function test_the_support_button_only_exists_when_a_number_is_configured(): void
    {
        $u = User::factory()->create();

        config(['lajanansanm.support_whatsapp' => null]);
        $this->actingAs($u)->get('/dashboard')->assertDontSee('wa.me');

        config(['lajanansanm.support_whatsapp' => '+509 3100-0000']);
        $this->actingAs($u)->get('/dashboard')->assertSee('https://wa.me/50931000000', false);
    }

    public function test_long_instructions_were_replaced_by_short_lines_and_hints(): void
    {
        $page = $this->actingAs($this->owner())->get(route('tontines.create'));

        $page->assertOk()->assertSee('data-hint', false)->assertDontSee('Ou pral ajoute manm yo epi defini lòd benefisyè yo');
    }

    public function test_the_hint_component_renders_its_text(): void
    {
        $this->blade('<x-hint>Eksplikasyon kout</x-hint>')->assertSee('data-hint', false)->assertSee('Eksplikasyon kout');
    }

    /* ---------- Journal anonyme ---------- */

    public function test_anyone_can_log_an_opening_without_an_account(): void
    {
        $this->postJson(route('help.log'), ['audience' => 'member', 'type' => 'open', 'faq_id' => 'declare'])->assertNoContent();

        $e = HelpEvent::firstOrFail();
        $this->assertSame('member', $e->audience);
        $this->assertSame('declare', $e->faq_id);
        $this->assertNull($e->query);
    }

    public function test_an_unknown_answer_id_is_not_stored(): void
    {
        $this->postJson(route('help.log'), ['audience' => 'creator', 'type' => 'open', 'faq_id' => 'inventé', 'query' => 'abc'])->assertNoContent();

        $e = HelpEvent::firstOrFail();
        $this->assertNull($e->faq_id);
        $this->assertNull($e->query);
    }

    public function test_phone_numbers_and_emails_are_stripped_from_free_questions(): void
    {
        $this->postJson(route('help.log'), ['audience' => 'member', 'type' => 'miss', 'query' => 'rele m nan 3712 3456 oswa +509 4288-7890 ak jean@exemple.com'])->assertNoContent();

        $q = HelpEvent::firstOrFail()->query;
        $this->assertStringNotContainsString('3712', $q);
        $this->assertStringNotContainsString('4288', $q);
        $this->assertStringNotContainsString('jean@', $q);
        $this->assertStringContainsString('rele m nan', $q);
    }

    public function test_long_questions_are_truncated_and_empty_ones_become_null(): void
    {
        $this->postJson(route('help.log'), ['audience' => 'creator', 'type' => 'miss', 'query' => str_repeat('a', 290)])->assertNoContent();
        $this->postJson(route('help.log'), ['audience' => 'creator', 'type' => 'miss', 'query' => '   '])->assertNoContent();

        $this->assertSame(120, mb_strlen(HelpEvent::orderBy('id')->first()->query));
        $this->assertNull(HelpEvent::orderByDesc('id')->first()->query);
    }

    public function test_the_log_rejects_bad_input(): void
    {
        $this->postJson(route('help.log'), ['audience' => 'hacker', 'type' => 'open'])->assertStatus(422);
        $this->postJson(route('help.log'), ['audience' => 'member', 'type' => 'delete'])->assertStatus(422);
        $this->postJson(route('help.log'), ['audience' => 'member', 'type' => 'miss', 'query' => str_repeat('x', 301)])->assertStatus(422);
        $this->assertSame(0, HelpEvent::count());
    }

    public function test_the_log_is_rate_limited(): void
    {
        $status = 204;
        for ($i = 0; $i < 70 && $status === 204; $i++) {
            $status = $this->postJson(route('help.log'), ['audience' => 'member', 'type' => 'open'])->getStatusCode();
        }

        $this->assertSame(429, $status);
        $this->assertLessThanOrEqual(61, HelpEvent::count());
    }

    /* ---------- Console ---------- */

    public function test_the_console_lists_unanswered_questions_and_popular_answers(): void
    {
        HelpEvent::create(['audience' => 'member', 'type' => 'miss', 'query' => 'ki jan pou m peye an kach']);
        HelpEvent::create(['audience' => 'member', 'type' => 'miss', 'query' => 'Ki jan pou m peye an kach']);
        HelpEvent::create(['audience' => 'creator', 'type' => 'open', 'faq_id' => 'payout']);
        HelpEvent::create(['audience' => 'creator', 'type' => 'open']);

        $res = $this->actingAs($this->owner())->get(route('admin.help'))->assertOk()->assertSee('ki jan pou m peye an kach');

        $this->assertEquals(2, $res->viewData('misses')->first()->c, 'les majuscules ne séparent pas les questions');
        $this->assertSame(1, $res->viewData('total'));
        $res->assertSee('Kijan pou m vèse lajan an bay benefisyè a?');
    }

    public function test_only_the_owner_can_open_the_help_statistics(): void
    {
        $this->get(route('admin.help'))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('admin.help'))->assertNotFound();
        $this->actingAs($this->owner())->get(route('admin.help', ['days' => 'x']))->assertOk();
    }

    /* ---------- Asistan IA Otonòm ---------- */

    public function test_ai_assistant_answers_natural_language_greetings_and_intent(): void
    {
        // Salutation
        $res = $this->postJson(route('help.ask'), [
            'audience' => 'creator',
            'query' => 'Bonjou asistan, kòman ou ye ?',
        ])->assertOk();

        $res->assertJsonStructure(['text', 'actions', 'related', 'tone', 'is_personalized']);
        $this->assertStringContainsString('Bonjou', implode(' ', $res->json('text')));

        // Question sur les règles (ex: virement beneficiaire)
        $resPayout = $this->postJson(route('help.ask'), [
            'audience' => 'creator',
            'query' => 'Kijan pou m vèse kòb la bay benefisyè a ?',
        ])->assertOk();

        $this->assertStringContainsString('Vèsman yo', implode(' ', $resPayout->json('text')));
        $this->assertSame('payout', $resPayout->json('matched_id'));
    }

    public function test_ai_assistant_inspects_live_user_context(): void
    {
        $user = User::factory()->create(['name' => 'Jean Baptiste']);

        // Utilisateur connecté sans tontine
        $res = $this->actingAs($user)->postJson(route('help.ask'), [
            'audience' => 'creator',
            'query' => 'Konbyen tontin mwen genyen ?',
        ])->assertOk();

        $this->assertTrue($res->json('is_personalized'));
        $this->assertStringContainsString('Ou pa gen okenn sòl', implode(' ', $res->json('text')));

        // Crée une tontine pour cet utilisateur
        \App\Models\Tontine::create([
            'admin_id' => $user->id,
            'name' => 'Tontine Fanmi',
            'amount_per_period' => 500,
            'period_type' => 'monthly',
            'start_date' => today(),
            'status' => 'active',
        ]);

        $res2 = $this->actingAs($user)->postJson(route('help.ask'), [
            'audience' => 'creator',
            'query' => 'Ki tontin mwen yo ?',
        ])->assertOk();

        $this->assertTrue($res2->json('is_personalized'));
        $this->assertStringContainsString('Tontine Fanmi', implode(' ', $res2->json('text')));
    }

    public function test_ai_assistant_handles_guest_and_member_queries(): void
    {
        // Visiteur / Invité
        $resGuest = $this->postJson(route('help.ask'), [
            'audience' => 'guest',
            'query' => 'Èske LajanAnsanm kenbe lajan an ?',
        ])->assertOk();

        $this->assertStringContainsString('pa kenbe lajan', implode(' ', $resGuest->json('text')));

        // Membre
        $resMember = $this->postJson(route('help.ask'), [
            'audience' => 'member',
            'query' => 'Kijan pou m deklare peman mwen ?',
        ])->assertOk();

        $this->assertStringContainsString('Mwen peye', implode(' ', $resMember->json('text')));
    }

    public function test_ai_assistant_logs_queries_and_rate_limits(): void
    {
        $this->postJson(route('help.ask'), [
            'audience' => 'guest',
            'query' => 'Kisa LajanAnsanm ye ?',
        ])->assertOk();

        $e = HelpEvent::where('audience', 'guest')->latest()->first();
        $this->assertNotNull($e);
        $this->assertSame('search', $e->type);
        $this->assertSame('what_is', $e->faq_id);

        // Validation invalide
        $this->postJson(route('help.ask'), [
            'audience' => 'unknown',
            'query' => 'Alo',
        ])->assertStatus(422);
    }
}
