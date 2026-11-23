<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_both_legal_pages_are_public_and_render(): void
    {
        $this->get(route('privacy'))->assertOk()->assertSee('POLITIQUE DE CONFIDENTIALITÉ', false);
        $this->get(route('terms'))->assertOk()->assertSee('CONDITIONS GÉNÉRALES', false);
    }

    public function test_the_brand_is_spelled_as_in_the_application(): void
    {
        $this->get(route('privacy'))->assertDontSee('LajanAnsam', false);
        $this->get(route('terms'))->assertDontSee('LajanAnsam', false);
    }

    public function test_the_terms_state_that_lajanansanm_holds_no_tontine_funds(): void
    {
        $this->get(route('terms'))->assertSee('sans transit par les comptes de LajanAnsanm', false);
    }
}
