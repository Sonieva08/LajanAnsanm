<?php

namespace App\Support;

interface AiAnswerer
{
    /**
     * @param  array<int, array>  $faqs
     */
    public function answer(string $question, array $faqs): ?string;
}
