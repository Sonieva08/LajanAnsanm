<?php

namespace App\Support;

/**
 * Recherche tolérante dans les réponses d'aide : accents, fautes de frappe et synonymes.
 */
final class HelpSearch
{
    private const STOP = [
        'pou', 'mwen', 'yon', 'nan', 'epi', 'ki', 'jan', 'kisa', 'fe', 'ak', 'sa', 'la', 'li', 'yo',
        'nou', 'pa', 'si', 'ka', 'te', 'se', 'kouman', 'comment', 'que', 'quoi', 'est', 'les', 'des', 'une', 'un',
        'et', 'je', 'tu', 'il', 'elle', 'on', 'vous', 'nous', 'mon', 'ma', 'mes', 'du', 'de', 'au', 'aux', 'en', 'dans',
        'the', 'and', 'for', 'to', 'is', 'of', 'ap', 'pral', 'deja', 'konn', 'konnen',
    ];

    private const SYNONYMS = [
        'manm' => ['membre', 'moun', 'patisipan', 'patisipe'],
        'tontin' => ['tontine', 'sol', 'sòl'],
        'abonman' => ['abonnement', 'abonnman', 'plan', 'abone', 'premium'],
        'peye' => ['payer', 'paye', 'paiement', 'peman', 'peyer', 'peyement', 'payment'],
        'vese' => ['verser', 'verse', 'vèse', 'vesman', 'vèsman', 'versement', 'benefisyè', 'benefisye'],
        'resevwa' => ['recevoir', 'reçoit', 'recu', 'recois', 'reçu', 'recevez', 'resevwar'],
        'konfime' => ['confirmer', 'confirme', 'valide', 'valider', 'verifye', 'verifier'],
        'anile' => ['annuler', 'annule', 'efase', 'supprimer', 'supprime', 'retire', 'retirer'],
        'kalandriye' => ['calendrier', 'kalandrye', 'kalandrie', 'tou', 'tours', 'tour'],
        'rejte' => ['refuser', 'refuse', 'refize', 'refuze', 'refusee'],
        'lyen' => ['lien', 'link', 'url'],
        'lekti' => ['lecture', 'bloke', 'bloque', 'bloquer', 'verrouille', 'verouille', 'lock'],
        'rapo' => ['rapport', 'rapports', 'report', 'reports'],
        'ajoute' => ['ajouter', 'ajout', 'ajoutte', 'inviter', 'envite'],
        'kotizasyon' => ['cotisation', 'cotisations'],
        'rapel' => ['rappel', 'relance', 'relancer', 'rapèl', 'rapèle'],
        'kont' => ['compte', 'konte', 'account'],
        'modpas' => ['mot de passe', 'password', 'motdepasse', 'pasword', 'mod pas'],
        'retard' => ['reta', 'retards', 'retardataire', 'en retard'],
        'komisyon' => ['commission', 'kòmisyon', 'pourcentage'],
        'envitasyon' => ['invitation'],
    ];

    private static array $synonymMap = [];

    public static function best(string $query, array $faqs): ?array
    {
        $queryTokens = array_values(array_unique(self::canonical(self::tokens($query))));
        if ($queryTokens === []) {
            return null;
        }

        $best = null;
        foreach ($faqs as $faq) {
            $questionTokens = self::canonical(self::tokens((string) ($faq['q'] ?? '')));
            $keywordTokens = self::canonical(self::tokens((string) ($faq['kw'] ?? '')));
            $score = 0;
            $matched = 0;

            foreach ($queryTokens as $token) {
                if (self::matches($token, $questionTokens)) {
                    $score += 2;
                    $matched++;
                } elseif (self::matches($token, $keywordTokens)) {
                    $score++;
                    $matched++;
                }
            }

            $coverage = $matched / count($queryTokens);
            if ($matched === 0 || ! ($coverage >= 0.5 || ($matched >= 2 && $score >= 3))) {
                continue;
            }

            if ($best === null || [$score, $coverage] > [$best['score'], $best['coverage']]) {
                $best = $faq + ['score' => $score, 'coverage' => $coverage];
            }
        }

        return $best;
    }

    public static function tokens(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = (string) preg_replace('/[\p{M}]/u', '', \Normalizer::normalize($text, \Normalizer::FORM_D) ?: $text);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $parts,
            fn (string $part) => mb_strlen($part, 'UTF-8') >= 2 && ! in_array($part, self::STOP, true)
        ));
    }

    private static function canonical(array $tokens): array
    {
        $map = self::synonyms();

        return array_map(fn (string $token) => $map[$token] ?? $token, $tokens);
    }

    private static function synonyms(): array
    {
        if (self::$synonymMap !== []) {
            return self::$synonymMap;
        }

        foreach (self::SYNONYMS as $canonical => $synonyms) {
            self::$synonymMap[$canonical] = $canonical;
            foreach ($synonyms as $synonym) {
                foreach (self::tokens($synonym) as $token) {
                    self::$synonymMap[$token] = $canonical;
                }
            }
        }

        return self::$synonymMap;
    }

    private static function matches(string $token, array $vocabulary): bool
    {
        foreach ($vocabulary as $word) {
            if ($token === $word) {
                return true;
            }

            $length = mb_strlen($token, 'UTF-8');
            $allowedDistance = $length >= 8 ? 2 : ($length >= 5 ? 1 : 0);
            if ($allowedDistance > 0
                && abs($length - mb_strlen($word, 'UTF-8')) <= $allowedDistance
                && levenshtein($token, $word) <= $allowedDistance) {
                return true;
            }
        }

        return false;
    }
}
