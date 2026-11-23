<?php

namespace App\Support;

final class NullAnswerer implements AiAnswerer
{
    public function answer(string $question, array $faqs): ?string
    {
        return null;
    }
}
