<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_both_legal_pages_are_public_and_render(): void
    {
        $this->withSession(['locale' => 'fr'])
            ->get(route('privacy'))->assertOk()->assertSee('POLITIQUE DE CONFIDENTIALITÉ', false);
        $this->withSession(['locale' => 'fr'])
            ->get(route('terms'))->assertOk()->assertSee('CONDITIONS GÉNÉRALES', false);
    }

    public function test_both_legal_pages_are_available_in_haitian_creole(): void
    {
        $this->withSession(['locale' => 'ht'])
            ->get(route('privacy'))->assertOk()
            ->assertSee('<html lang="ht">', false)
            ->assertSee('POLITIK KONFIDANSYALITE', false)
            ->assertSee('KATEGORI DONE NOU RANMASE', false);

        $this->withSession(['locale' => 'ht'])
            ->get(route('terms'))->assertOk()
            ->assertSee('<html lang="ht">', false)
            ->assertSee('KONDISYON JENERAL ITILIZASYON', false)
            ->assertSee('Platfòm nan pa resevwa, pa konsève epi pa distribye lajan sòl yo.', false);
    }

    public function test_privacy_policy_matches_admin_access_and_member_link_behavior(): void
    {
        $this->withSession(['locale' => 'fr'])
            ->get(route('privacy'))->assertOk()
            ->assertSee('l’adresse IP figure également dans l’export CSV.', false)
            ->assertSee('Le lien du portail membre n’a pas de date d’expiration automatique', false)
            ->assertSee('ni durée précise de conservation', false);

        $this->withSession(['locale' => 'ht'])
            ->get(route('privacy'))->assertOk()
            ->assertSee('Lyen pòtay manm nan pa ekspire otomatikman', false)
            ->assertSee('ni yon dire egzak pou konsève done manm yo', false);
    }

    public function test_terms_describe_manual_renewal_and_no_refund_feature(): void
    {
        $this->withSession(['locale' => 'fr'])
            ->get(route('terms'))->assertOk()
            ->assertSee('L’application ne renouvelle pas automatiquement les abonnements', false)
            ->assertSee('L’application ne comporte pas de fonction de demande ni de traitement de remboursement.', false);

        $this->withSession(['locale' => 'ht'])
            ->get(route('terms'))->assertOk()
            ->assertSee('Aplikasyon an pa renouvle abònman yo otomatikman', false)
            ->assertSee('Aplikasyon an pa gen fonksyon pou mande ni trete ranbousman.', false);
    }

    public function test_the_brand_is_spelled_as_in_the_application(): void
    {
        $this->withSession(['locale' => 'fr'])->get(route('privacy'))->assertDontSee('LajanAnsam', false);
        $this->withSession(['locale' => 'fr'])->get(route('terms'))->assertDontSee('LajanAnsam', false);
    }

    public function test_the_terms_state_that_lajanansanm_holds_no_tontine_funds(): void
    {
        $this->withSession(['locale' => 'fr'])
            ->get(route('terms'))->assertSee('La plateforme ne reçoit pas, ne conserve pas et ne distribue pas les fonds des tontines.', false);
    }
}
