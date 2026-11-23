<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejette les mots de passe trop courants ou trop prévisibles.
 *
 * La liste couvre les 200 mots de passe les plus fréquents dans les fuites
 * ainsi que des patterns évidents (clavier, répétitions, prénoms génériques).
 */
class NotCommonPassword implements ValidationRule
{
    /**
     * @var list<string> Mots de passe et patterns interdits (en minuscules).
     */
    private const COMMON_PASSWORDS = [
        // Classiques universels
        '123456', '12345678', '123456789', '1234567890',
        'password', 'password1', 'password123',
        'azerty', 'azerty123', 'azertyuiop',
        'qwerty', 'qwerty123', 'qwertyuiop',
        '111111', '1111111111', '000000',
        'abc123', 'abcdef', 'abcd1234',
        'iloveyou', 'sunshine', 'princess',
        'welcome', 'letmein', 'monkey', 'dragon',
        'master', 'superman', 'batman',
        'admin', 'admin123', 'root', 'toor',
        'test', 'test123', 'demo', 'guest',
        'login', 'pass', 'passw0rd',
        // Patterns clavier
        '1q2w3e4r', 'qazwsx', 'zxcvbn', 'qweasdzxc',
        // Répétitions
        'aaaaaa', 'aaaa1111', 'aabbccdd',
        // Dates et années simples
        '01012000', '01011990', '19801234',
        // Prénoms / noms génériques
        'michael', 'jennifer', 'jessica', 'thomas', 'sophie',
        // Mots haïtiens / français courants
        'bonjou', 'bonsoir', 'maman', 'papa', 'manman',
        'haiti', 'haiti123', 'portauprince',
        'tontine', 'lajanansanm',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $lower = mb_strtolower((string) $value);

        if (in_array($lower, self::COMMON_PASSWORDS, true)) {
            $fail(__('validation.password_common'));
            return;
        }

        // Rejette aussi les mots de passe entièrement composés du même caractère répété
        if (strlen($lower) > 0 && preg_match('/^(.)\1+$/', $lower)) {
            $fail(__('validation.password_common'));
        }
    }
}

