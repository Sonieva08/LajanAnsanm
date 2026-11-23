<?php

namespace App\Support;

use App\Models\Cycle;
use App\Models\HelpEvent;
use App\Models\PaymentDeclaration;
use App\Models\Plan;
use App\Models\Tontine;
use App\Models\User;

final class HelpAssistant
{
    /**
     * Traite une question en langage naturel et génère une réponse autonome intelligente.
     */
    public static function answer(string $query, string $audience = 'creator', ?User $user = null): array
    {
        $raw = trim($query);
        $norm = self::normalize($raw);

        // 1. Détection des salutations et politesses
        if (self::isGreeting($norm)) {
            return self::greetingResponse($user, $audience) + ['matched_id' => null];
        }

        if (self::isThanks($norm)) {
            return [
                'text' => [
                    __('Pa gen pwoblèm ! Mwen toujou la pou ede w.'),
                    __('Si w gen nenpòt lòt kesyon sou sòl ou yo, kalandriye a, oswa manm yo, poze m li dirèkteman !'),
                ],
                'actions' => [],
                'related' => ['add_member', 'order', 'payout'],
                'tone' => 'normal',
                'is_personalized' => false,
                'matched_id' => null,
            ];
        }

        // 2. Questions personnalisées sur les données réelles de l'utilisateur connecté
        if ($user && ($personalized = self::checkUserContext($norm, $user))) {
            return $personalized + ['matched_id' => null];
        }

        // 3. Recherche thématique approfondie avec synonymes et intentions
        $intent = self::detectIntent($norm);
        if ($intent) {
            $intentFaqMap = [
                'create_tontine'   => 'how_to_start',
                'add_member'       => 'add_member',
                'order_and_shuffle'=> 'order',
                'commission'       => 'commission',
                'payments'         => 'declare',
                'cancel_payment'   => 'cancel_payment',
                'payout'           => 'payout',
                'payout_problem'   => 'payout_problem',
                'late_reminders'   => 'late',
                'restart_round'    => 'restart',
                'reports'          => 'reports',
                'member_portal'    => 'portal',
                'plans'            => 'plans',
                'security'         => 'holds_money',
                'platform_info'    => 'what_is',
                'tontine_info'     => 'tontine_basics',
                'delete_tontine'   => null,
                'edit_tontine'     => null,
                'remove_member'    => 'add_member',
                'notifications'    => null,
                'forgot_password'  => 'forgot_password',
                'export_data'      => 'reports',
                'whatsapp'         => null,
                'free_plan_limits' => 'readonly',
                'grace_period'     => 'grace',
                'readonly_mode'    => 'readonly',
                'declare_received' => 'received',
                'payment_methods'  => 'payment_methods',
                'two_factor'       => null,
            ];

            if (in_array($intent, ['platform_info', 'tontine_info'], true)) {
                $faq = HelpCenter::find($audience, $intentFaqMap[$intent]);

                if ($faq) {
                    return [
                        'text' => $faq['a'],
                        'actions' => $faq['actions'] ?? [],
                        'related' => $faq['related'] ?? [],
                        'tone' => $faq['tone'] ?? 'normal',
                        'is_personalized' => false,
                        'matched_id' => $faq['id'],
                    ];
                }
            }

            return self::buildIntentResponse($intent, $audience) + [
                'matched_id' => $intentFaqMap[$intent] ?? null,
            ];
        }

        // 4. Recherche sémantique / par mots-clés dans le catalogue FAQ
        $bestFaq = self::findBestFaq($norm, $audience);
        if ($bestFaq && $bestFaq['score'] >= 2) {
            return [
                'text' => $bestFaq['faq']['a'],
                'actions' => $bestFaq['faq']['actions'] ?? [],
                'related' => $bestFaq['faq']['related'] ?? [],
                'tone' => $bestFaq['faq']['tone'] ?? 'normal',
                'is_personalized' => false,
                'matched_id' => $bestFaq['faq']['id'] ?? null,
            ];
        }

        // 5. Synthèse générale intelligente si aucun mot-clé direct n'est trouvé
        return self::fallbackResponse($raw, $audience) + ['matched_id' => null];
    }

    private static function normalize(string $text): string
    {
        $s = mb_strtolower($text);
        // Retirer les accents
        $s = (string) preg_replace('/[\p{M}]/u', '', \Normalizer::normalize($s, \Normalizer::FORM_D) ?: $s);
        // Remplacer ponctuations par espaces
        $s = (string) preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
        // Espaces multiples
        $s = (string) preg_replace('/\s+/u', ' ', $s);

        return trim($s);
    }

    private static function isGreeting(string $norm): bool
    {
        $greetings = ['bonjou', 'bonjour', 'bonswa', 'bonsoir', 'sak pase', 'sa k pase', 'alo', 'salut', 'hey', 'hi', 'hello', 'koman ou ye', 'kòman ou ye'];
        foreach ($greetings as $g) {
            if ($norm === $g || str_starts_with($norm, $g . ' ') || str_ends_with($norm, ' ' . $g)) {
                return true;
            }
        }

        return false;
    }

    private static function isThanks(string $norm): bool
    {
        $thanks = ['mesi', 'mèsi', 'merci', 'thanks', 'thank you', 'mesi anpil', 'mèsi anpil'];
        foreach ($thanks as $t) {
            if ($norm === $t || str_starts_with($norm, $t . ' ') || str_contains($norm, ' ' . $t)) {
                return true;
            }
        }

        return false;
    }

    private static function greetingResponse(?User $user, string $audience): array
    {
        $name = $user ? explode(' ', trim($user->name))[0] : '';
        $intro = $name !== '' ? __('Bonjou :name !', ['name' => $name]) : __('Bonjou !');

        return [
            'text' => [
                $intro . ' ' . __('Mwen se Jazon. Mwen la pou gide w ak reponn tout kesyon ou sou platfòm nan san okenn difikilte.'),
                __('Ou ka mande m nenpòt bagay : kijan pou kreye yon sòl, jere manm yo, anrejistre peman, voye rapèl WhatsApp, oswa gade kalandriye w !'),
            ],
            'actions' => $user ? [
                [__('Akèy'), route('dashboard')],
                [__('Kreye yon sòl'), route('tontines.create')],
            ] : [
                [__('Konekte'), route('login')],
                [__('Enskri'), route('register')],
            ],
            'related' => ['add_member', 'order', 'payout', 'declare'],
            'tone' => 'normal',
            'is_personalized' => false,
        ];
    }

    /**
     * Analyse si la question porte sur les données en temps réel de l'utilisateur.
     */
    private static function checkUserContext(string $norm, User $user): ?array
    {
        // A. "Konbyen tontin mwen genyen?" / "Ki tontin mwen yo?"
        if (self::matchesAny($norm, ['konbyen tontin', 'tontin mwen yo', 'lis tontin', 'mes tontines', 'combien de tontines'])) {
            $tontines = Tontine::where('admin_id', $user->id)->get();
            $count = $tontines->count();
            $active = $tontines->where('status', 'active')->count();

            if ($count === 0) {
                return [
                    'text' => [
                        __('Ou pa gen okenn sòl anrejistre pou kounye a.'),
                        __('Ou ka kòmanse pa kreye premye sòl ou pou rasanble manm fanmi oswa zanmi w !'),
                    ],
                    'actions' => [[__('Kreye yon sòl'), route('tontines.create')]],
                    'related' => ['add_member', 'order'],
                    'tone' => 'info',
                    'is_personalized' => true,
                ];
            }

            $names = $tontines->pluck('name')->take(3)->implode(', ');
            if ($count > 3) {
                $names .= ' ' . __('ak :count lòt', ['count' => $count - 3]);
            }

            return [
                'text' => [
                    __('Ou gen :total sòl la kont ou (:active aktif).', ['total' => $count, 'active' => $active]),
                    __('Men kèk nan yo : :names.', ['names' => $names]),
                ],
                'actions' => [[__('Wè tout sòl yo'), route('tontines.index')]],
                'related' => ['payout', 'reports'],
                'tone' => 'normal',
                'is_personalized' => true,
            ];
        }

        // B. "Èske mwen gen moun ki an reta?" / "Manm an reta"
        if (self::matchesAny($norm, ['an reta', 'retard', 'reta', 'qui est en retard', 'kiyès ki an reta', 'konbyen moun ki an reta'])) {
            $userTontineIds = Tontine::where('admin_id', $user->id)->pluck('id');
            $openCycles = Cycle::whereIn('tontine_id', $userTontineIds)->where('status', 'open')->get();

            $lateCount = 0;
            $lateDetails = [];

            foreach ($openCycles as $c) {
                if ($c->isLate()) {
                    $lateCount++;
                    $lateDetails[] = $c->tontine->name . ' (' . __('Tou') . ' ' . $c->cycle_number . ')';
                }
            }

            if ($lateCount === 0) {
                return [
                    'text' => [
                        __('🎉 Bon nouvèl ! Pa gen okenn tou ki an reta nan sòl ou yo pou kounye a.'),
                        __('Tout bagay ap mache nan lòd selon kalandriye a.'),
                    ],
                    'actions' => [[__('Wè sòl mwen yo'), route('tontines.index')]],
                    'related' => ['late', 'payout'],
                    'tone' => 'normal',
                    'is_personalized' => true,
                ];
            }

            return [
                'text' => [
                    __('⚠️ Ou gen :count tou ki gen dat limit depase (an reta).', ['count' => $lateCount]),
                    __('Sa konsène : :details.', ['details' => implode(', ', array_slice($lateDetails, 0, 3))]),
                    __('Ou ka louvri paj kotizasyon sòl la pou voye rapèl WhatsApp bay manm yo dirèkteman.'),
                ],
                'actions' => [[__('Wè sòl yo'), route('tontines.index')]],
                'related' => ['late', 'cancel_payment'],
                'tone' => 'alert',
                'is_personalized' => true,
            ];
        }

        // C. "Kiyès ki pwochen benefisyè a?" / "Pwochen tou"
        if (self::matchesAny($norm, ['pwochen benefisye', 'prochain beneficiaire', 'ki moun k ap resevwa', 'pwochen tou', 'kiyes ki pou touche', 'kiyès ki touche'])) {
            $tontine = Tontine::where('admin_id', $user->id)->where('status', 'active')->first();
            if (! $tontine) {
                return null;
            }

            $currentCycle = $tontine->currentCycle();
            if ($currentCycle && $currentCycle->beneficiary) {
                $cur = config('lajanansanm.currency');
                $netPot = number_format($tontine->netPotAmount(), 0, '.', ',');

                return [
                    'text' => [
                        __('Pou sòl « :tontine », benefisyè tou aktyèl la (#:round) se **:name**.', [
                            'tontine' => $tontine->name,
                            'round' => $currentCycle->cycle_number,
                            'name' => $currentCycle->beneficiary->name,
                        ]),
                        __('Montan l ap resevwa se :amount :cur (dat limit: :date).', [
                            'amount' => $netPot,
                            'cur' => $cur,
                            'date' => $currentCycle->deadline->format('d/m/Y'),
                        ]),
                    ],
                    'actions' => [[__('Gade sòl sa a'), route('tontines.show', $tontine)]],
                    'related' => ['payout', 'commission'],
                    'tone' => 'normal',
                    'is_personalized' => true,
                ];
            }
        }

        // D. "Èske mwen gen deklarasyon peman k ap tann?"
        if (self::matchesAny($norm, ['deklarasyon', 'declaration', 'peman k ap tann', 'attente'])) {
            $userTontineIds = Tontine::where('admin_id', $user->id)->pluck('id');
            $pendingCount = PaymentDeclaration::whereIn('tontine_id', $userTontineIds)->where('status', 'pending')->count();

            if ($pendingCount > 0) {
                return [
                    'text' => [
                        __('Ou gen :count deklarasyon peman manm yo voye ki ap tann konfimasyon ou.', ['count' => $pendingCount]),
                        __('Verifye nimewo tranzaksyon MonCash/NatCash yo sou kont ou anvan ou aksepte yo.'),
                    ],
                    'actions' => [[__('Wè sòl mwen yo'), route('tontines.index')]],
                    'related' => ['declare', 'txn'],
                    'tone' => 'info',
                    'is_personalized' => true,
                ];
            }
        }

        // E. "Ki plan mwen ye?" / "Abònman mwen"
        if (self::matchesAny($norm, ['abònman mwen', 'mon abonnement', 'ki plan m genyen', 'plan mwen'])) {
            $entitlements = Entitlements::for($user);

            return [
                'text' => [
                    __('W ap itilize plan **:name** (estati: :state).', [
                        'name' => $entitlements->plan->name,
                        'state' => $entitlements->state,
                    ]),
                    $entitlements->isPaid() && $entitlements->daysLeft() !== null
                        ? __('Li rete ou anviwon :days jou sou abònman sa a.', ['days' => $entitlements->daysLeft()])
                        : __('Ou ka konsilte oswa amelyore plan ou a nenpòt kilè pou debloke plis avantaj.'),
                ],
                'actions' => [
                    [__('Abònman mwen'), route('subscription')],
                    [__('Wè plan yo'), route('plans')],
                ],
                'related' => ['plans', 'grace'],
                'tone' => 'normal',
                'is_personalized' => true,
            ];
        }

        // F. "Konbyen manm mwen genyen?" / "Lis manm yo"
        if (self::matchesAny($norm, ['konbyen manm', 'lis manm', 'ki manm', 'mes membres', 'combien de membres', 'manm mwen yo'])) {
            $tontine = Tontine::where('admin_id', $user->id)->where('status', 'active')->first();
            if ($tontine) {
                $memberCount = $tontine->members()->count();

                return [
                    'text' => [
                        __('Sòl « :name » ou a gen **:count manm** anrejistrè.', ['name' => $tontine->name, 'count' => $memberCount]),
                        __('Ou ka wè, ajoute, oswa jere manm yo nan paj sòl la.'),
                    ],
                    'actions' => [[__('Jere manm yo'), route('tontines.show', $tontine)]],
                    'related' => ['add_member', 'order'],
                    'tone' => 'normal',
                    'is_personalized' => true,
                ];
            }
        }

        // G. "Konbyen lajan m ap resevwa?" / "Montan tou mwen"
        if (self::matchesAny($norm, ['konbyen lajan m ap resevwa', 'montan tou mwen', 'kombyen kob', 'montan m ap jwenn', 'ki montan', 'lajan m ap touche'])) {
            $tontine = Tontine::where('admin_id', $user->id)->where('status', 'active')->first();
            if ($tontine) {
                $cur = config('lajanansanm.currency');
                $gross = number_format($tontine->potAmount(), 0, '.', ',');
                $net = number_format($tontine->netPotAmount(), 0, '.', ',');

                return [
                    'text' => [
                        __('Nan sòl « :name », montan brit chak tou se **:gross :cur**.', ['name' => $tontine->name, 'gross' => $gross, 'cur' => $cur]),
                        __('Apre kòmisyon òganizatè a, benefisyè a resevwa **:net :cur** nèt.', ['net' => $net, 'cur' => $cur]),
                    ],
                    'actions' => [[__('Gade sòl sa a'), route('tontines.show', $tontine)]],
                    'related' => ['payout', 'commission'],
                    'tone' => 'normal',
                    'is_personalized' => true,
                ];
            }
        }

        return null;
    }

    private static function detectIntent(string $norm): ?string
    {
        $intents = [
            'create_tontine'   => ['kreye tontin', 'kreye yon tontin', 'komanse tontin', 'creer tontine', 'nouvo tontin', 'ouvril yon sol', 'kreye sol', 'ouvri yon tontin', 'kòmanse yon sol'],
            'add_member'       => ['ajoute manm', 'ajouter membre', 'mete manm', 'met moun', 'nouvo manm', 'kantite manm', 'envite manm', 'ajoute moun', 'nouvo patisipan'],
            'order_and_shuffle'=> ['lod resepsyon', 'lòd resepsyon', 'tiraj o so', 'tiraj o sò', 'melanje', 'changer ordre', 'ki jan lod la fet', 'ki jan lòd la fèt', 'chanje lòd', 'lòd benefisyè'],
            'commission'       => ['kòmisyon', 'komisyon', 'commission', 'pousantaj oganizate', 'retni', 'kòb òganizatè', 'pousantaj', 'frè òganizatè', 'pourcentage'],
            'payments'         => ['kijan pou m peye', 'deklare peman', 'anrejistre peman', 'enregistrer paiement', 'peye manm', 'moncash', 'natcash', 'kijan pou peye', 'fè yon peman', 'peye tontin'],
            'cancel_payment'   => ['anile peman', 'annuler paiement', 'efase peman', 'korije peman', 'anile yon peman', 'retire peman', 'supprime peman'],
            'payout_problem'   => ['benefisye pa resevwa', 'benefisyè pa resevwa', 'mwen pa resevwa lajan', 'peman pa rive', 'lajan pa rive', 'konteste vèsman'],
            'payout'           => ['vese lajan', 'vèse lajan', 'verser argent', 'bay benefisye', 'femen tou', 'klotire', 'bay lajan', 'fèmen yon tou', 'vèsman', 'benefisyè a resevwa'],
            'late_reminders'   => ['rapel whatsapp', 'rapèl whatsapp', 'moun an reta', 'rappel whatsapp', 'retade', 'voye rapel', 'voye yon rapèl', 'rete dwèt', 'ki moun ki pa peye', 'reta'],
            'restart_round'    => ['relanse sik', 'relancer cycle', 'nouvo sik', 'recommencer', 'kòl fini', 'kol fini', 'relanse yon sik', 'nouvo kòl', 'yon lòt sik'],
            'reports'          => ['telechaje rapo', 'rapò pdf', 'rapo excel', 'csv', 'enprime', 'telecharger rapport', 'jwenn rapò', 'rapò tontin', 'ekspòte', 'eksporte'],
            'member_portal'    => ['espas manm', 'lyen pesonel', 'portail membre', 'lien personnel', 'lyen pa mache', 'pòtay manm', 'espas pèsonèl', 'kijan manm antre', 'lyen manm'],
            'plans'            => ['plan premium', 'peye abonman', 'abònman', 'prix', 'pri', 'tarif', 'abonnman', 'plan gratis', 'plan peyan', 'chanje plan', 'amelyore plan', 'ki plan'],
            'security'         => ['eske se nou k kenbe kob', 'sekirite', 'vòlè', 'eske lajan an an sekirite', 'securite', 'ki jan lajan pwoteje', 'eske sa sekirize', 'konfyans'],
            'platform_info'    => ['kisa lajanansanm ye', 'kisa lajan ansanm ye', 'kisa lajan ansamn ye', 'kisa lajan an sanm ye', 'kisa lajanansnanm ye', 'kisa lajanasnanm ye', 'lajan ansanm se kisa', 'lajanasnanm se kisa', 'c est quoi lajanansanm', 'c est quoi lajan ansanm', 'ki sa ki lajanansanm', 'ki sa lajan ansanm ye', 'kijan sa mache', 'ki jan aplikasyon an mache', 'ki sa nou ye', 'kisa nou ye'],
            'tontine_info'     => ['kisa tontin ye', 'kisa yon tontin ye', 'ki sa tontin ye', 'ki sa yon tontin ye', 'kisa tontine ye', 'kisa yon tontine ye', 'ki sa yon tontine ye', 'tontin se kisa', 'tontine c est quoi', 'c est quoi une tontine', 'qu est ce qu une tontine', 'definisyon tontin', 'definisyon tontine'],
            'delete_tontine'   => ['efase tontin', 'siprime tontin', 'detruire tontine', 'anile tontin', 'supprimer tontine', 'retire tontin', 'elimine tontin'],
            'edit_tontine'     => ['modifye tontin', 'chanje non tontin', 'edite tontin', 'modifier tontine', 'chanje montan', 'chanje frekans', 'mete ajou tontin'],
            'remove_member'    => ['retire manm', 'efase manm', 'supprimer membre', 'anile manm', 'bloke manm', 'elimine manm', 'retire yon moun'],
            'notifications'    => ['notifikasyon', 'notification', 'alèt', 'alerte', 'pousnotifikasyon', 'push notification', 'mesaj avètisman', 'avètisman'],
            'two_factor'       => ['de faktè', 'deuxieme facteur', 'two factor', '2fa', 'sekirite kont', 'double authentification', 'verifye kont'],
            'forgot_password'  => ['bliye modpas', 'modpas bliye', 'password oublié', 'rekipere modpas', 'reset password', 'chanje modpas', 'nouvo modpas'],
            'export_data'      => ['ekspòte done', 'telechaje done', 'exporter donnees', 'save done', 'backup', 'extract done', 'csv telechaje'],
            'whatsapp'         => ['whatsapp', 'sms', 'telefon', 'nimewo', 'kontak', 'mesaj', 'voye mesaj', 'whats app'],
            'free_plan_limits' => ['limit plan gratis', 'limit gratis', 'kantite tontin gratis', 'max tontin', 'max manm', 'plan limite', 'depase limit', 'pa ka ajoute'],
            'grace_period'     => ['peryòd gras', 'grace period', 'jou gras', 'apre ekspire', 'abonnman ekspire', 'abònman fini', 'ki pase apre abonnman'],
            'readonly_mode'    => ['lekti sèlman', 'read only', 'pa ka modifye', 'bloke', 'limite', 'pa ka chanje', 'pa ka ajoute manm', 'tontin bloke'],
            'declare_received' => ['mwen resevwa lajan', 'konfime resepsyon', 'mwen pa resevwa', 'lajan pa rive', 'konfimasyon resepsyon', 'di mwen resevwa', 'peman pa rive'],
            'payment_methods'  => ['moncash', 'natcash', 'labank', 'sogebank', 'unibank', 'kach', 'men nan men', 'mwayen peman', 'kijan pou peye', 'ki mwayen', 'transfert banque'],
        ];

        foreach ($intents as $intent => $keywords) {
            if (self::matchesAny($norm, $keywords)) {
                return $intent;
            }
        }

        if (preg_match('/\b(tontin|tontine)\b/u', $norm)
            && self::matchesAny($norm, ['kisa', 'ki sa', 'sa vle di', 'definisyon', 'c est quoi', 'qu est ce'])) {
            return 'tontine_info';
        }

        return null;
    }

    private static function buildIntentResponse(string $intent, string $audience): array
    {
        return match ($intent) {
            'create_tontine' => [
                'text' => [
                    __('Pou kreye yon nouvo sòl, peze bouton « Kreye yon sòl » oswa « Nouvo » nan meni an.'),
                    __('W ap mete non sòl la, montan chak moun dwe peye, frekans lan (chak semèn oswa chak mwa), ak dat premye peman an.'),
                    __('Apre w fin kreye l, w ap ka ajoute manm yo epi defini lòd benefisyè yo anvan w lanse kalandriye a.'),
                ],
                'actions' => [[__('Kreye yon sòl kounye a'), route('tontines.create')]],
                'related' => ['add_member', 'order'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'add_member' => [
                'text' => [
                    __('Pou ajoute yon manm : louvri sòl la, peze « Jere manm yo », epi klike sou « Ajoute manm ».'),
                    __('Mete non konplè patisipan an ak nimewo telefòn li.'),
                    __('Atansyon : ou ka ajoute manm sèlman pandan sòl la an faz preparasyon (anvan kalandriye tou yo jenere).'),
                ],
                'actions' => [[__('Wè sòl mwen yo'), route('tontines.index')]],
                'related' => ['order', 'readonly'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'order_and_shuffle' => [
                'text' => [
                    __('Manm nan pozisyon 1 resevwa lajan an nan tou 1, pozisyon 2 nan tou 2, elatriye.'),
                    __('Ou ka monte oswa desann manm yo ak flèch yo (▲▼), oswa peze bouton tiraj o sò a pou melanje lòd la o aza.'),
                    __('Depi kalandriye a fin jenere, lòd benefisyè yo vin bloke nèt pou pwoteje konfyans tout patisipan yo.'),
                ],
                'actions' => [],
                'related' => ['add_member', 'payout'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'commission' => [
                'text' => [
                    __('Ou ka chwazi yon pousantaj kòmisyon òganizatè (0 a 15%) lè w ap kreye oswa modifye sòl la.'),
                    __('Sistèm nan retire pousantaj sa a otomatikman sou kòb total la chak fwa yon benefisyè ap resevwa pa l.'),
                    __('Pousantaj sa a bloke depi kalandriye a kòmanse, epi li disponib sou plan peyan yo.'),
                ],
                'actions' => [[__('Wè plan yo'), route('plans')]],
                'related' => ['payout', 'plans'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'payments' => [
                'text' => [
                    __('Lè tou a ouvè, manm nan gade montan kontribisyon an ak kont òganizatè a mete disponib la, epi li fè transfè a dirèkteman. LajanAnsanm pa resevwa lajan an.'),
                    __('Sou lyen pèsonèl li, manm nan peze « Mwen peye », chwazi metòd la, antre nimewo li te peye avè l la ak nimewo tranzaksyon an; resi a ak nòt la opsyonèl.'),
                    __('Deklarasyon an rete an atant jiskaske òganizatè a verifye lajan an epi apwouve li. Si li rejte l, manm nan ka korije detay yo epi voye l ankò.'),
                ],
                'actions' => [],
                'related' => ['contribution_amount', 'txn', 'wait', 'rejected'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'cancel_payment' => [
                'text' => [
                    __('Si w te anrejistre yon peman pa erè, ou ka anile li pandan tou aktyèl la toujou ouvè.'),
                    __('Ale sou paj « Peman yo », epi peze ti ikòn kwa (✕) ki bò kote peman an.'),
                    __('Atansyon : depi vèsman an fèt bay benefisyè a epi tou a fèmen, li pa posib pou anile peman an ankò.'),
                ],
                'actions' => [],
                'related' => ['payout', 'payments'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'payout' => [
                'text' => [
                    __('Ale nan « Vèsman yo » epi chwazi « Vèse » sou tou ki ouvè a. Fòk gen yon benefisyè ak omwen yon kontribisyon; sinon pa gen vèsman pou fè.'),
                    __('Si gen manm ki poko peye, ou ka tann oswa konfime ou vle vèse montan ki deja ranmase a sèlman. Montan nèt la se lajan sa a mwens kòmisyon ki aplikab.'),
                    __('Fè transfè a deyò LajanAnsanm, epi anrejistre metòd, kont moun ki voye a ak nimewo tranzaksyon an; resi an imaj opsyonèl. Tou a ap fèmen; pwochen an ap ouvri otomatikman.'),
                ],
                'actions' => [],
                'related' => ['payout_proof', 'payout_problem', 'commission', 'cancel_payment'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'payout_problem' => [
                'text' => [
                    __('Verifye nimewo tranzaksyon an ak resi a, epi pale ak benefisyè a pou chèche konnen sa k pase. Pa voye yon lòt fwa san ou pa verifye premye transfè a.'),
                    __('Benefisyè a ka rapòte pwoblèm nan nan espas manm li. Apre ou fin verifye oswa regle sa, mande l konfime ankò nan espas li.'),
                ],
                'actions' => [],
                'related' => ['payout', 'payout_proof'],
                'tone' => 'alert',
                'is_personalized' => false,
            ],

            'late_reminders' => [
                'text' => [
                    __('Yon manm klase « An reta » depi dat limit vèsman an fin pase.'),
                    __('Avèk yon plan ki gen fonksyon rapèl WhatsApp, ou ka klike sou « Voye rapèl » pou louvri WhatsApp ak yon mesaj politès tou pare.'),
                    __('Ou ka toujou anrejistre peman yon manm menm si li te an reta san okenn penalite sistèm.'),
                ],
                'actions' => [[__('Wè plan yo'), route('plans')]],
                'related' => ['plans', 'payout'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'restart_round' => [
                'text' => [
                    __('Lè tout tou nan yon kòl fin fèt, sòl la pase kòm « Fini ».'),
                    __('Ou ka ale nan « Kalandriye tou yo », chwazi dat premye peman nouvo kòl la, epi peze « Relanse yon nouvo kòl ».'),
                    __('Sa pèmèt ou ajoute oswa retire manm epi melanje lòd la anvan w relanse.'),
                ],
                'actions' => [],
                'related' => ['add_member', 'plans'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'reports' => [
                'text' => [
                    __('Ou ka konsilte rapò chak tou ak rapò tout kòl la nèt lè w louvri sòl la epi klike sou « Rapò yo ».'),
                    __('Ou ka enprime rapò a an PDF gratis nenpòt kilè.'),
                    __('Ekspòtasyon an dosye Excel (CSV) pou kontablite disponib sou plan peyan yo.'),
                ],
                'actions' => [],
                'related' => ['plans'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'member_portal' => [
                'text' => [
                    __('Chak manm gen yon lyen pèsonèl sekirize (/m/{token}) ki pa mande pou l kreye yon kont.'),
                    __('Sou paj sa a, manm nan wè kalandriye a, dat limit li, li ka deklare peman l, epi konfime lè l resevwa kòb tou pa l.'),
                    __('Òganizatè a ka renouvle lyen yon manm nenpòt kilè si manm nan ta pèdi l.'),
                ],
                'actions' => [],
                'related' => ['declare', 'received'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'plans' => [
                'text' => [
                    __('LajanAnsanm ofri yon plan Gratis (jiska 1 sòl ak 10 manm) ak plan Premium (Pro) ki debloke plis sòl, ekspòtasyon CSV, rapèl WhatsApp ak kòmisyon.'),
                    __('Ou ka peye abònman w dirèkteman sou sit la via MonCash, NatCash oswa transfè labank.'),
                ],
                'actions' => [
                    [__('Wè tout plan yo'), route('plans')],
                    [__('Abònman mwen'), route('subscription')],
                ],
                'related' => ['grace', 'commission'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'security' => [
                'text' => [
                    __('LajanAnsanm pa janm kenbe lajan manm yo. Nou se yon zouti jesyon ak transparans.'),
                    __('Tout tranzaksyon fèt dirèkteman ant manm yo ak òganizatè a pa mwayen MonCash, NatCash oswa labank.'),
                    __('Platfòm nan anrejistre tout aksyon nan yon jounal aktivite pou evite nenpòt diskisyon oswa dout.'),
                ],
                'actions' => [],
                'related' => ['portal', 'payout'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'delete_tontine' => [
                'text' => [
                    __('Ou ka efase yon sòl ki nan faz preparasyon (anvan kalandriye a jenere) nan paj « Sòl yo ».'),
                    __('Si kalandriye a deja jenere epi tou yo te kòmanse, ou pa ka efase sòl la dirèkteman men ou ka ale nan réglaj pou fèmen l.'),
                    __('⚠️ Efase yon sòl retire tout done li ki gen ladan l, manm yo ak kalandriye a nèt.'),
                ],
                'actions' => [[__('Wè sòl mwen yo'), route('tontines.index')]],
                'related' => ['add_member', 'readonly'],
                'tone' => 'alert',
                'is_personalized' => false,
            ],

            'edit_tontine' => [
                'text' => [
                    __('Ou ka modifye non, montan, oswa frekans yon sòl si kalandriye a poko jenere epi li toujou nan faz preparasyon.'),
                    __('Depi ou jenere kalandriye a, pifò paramèt yo vin bloke pou pwoteje konfyans manm yo.'),
                    __('Pou chanje peryòd peman, ou ka efase epi rekòmanse, oswa kontakte sipò a pou ka espesyal.'),
                ],
                'actions' => [[__('Wè sòl mwen yo'), route('tontines.index')]],
                'related' => ['add_member', 'restart_round'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'remove_member' => [
                'text' => [
                    __('Ou ka retire yon manm sèlman si kalandriye a poko jenere (faz preparasyon).'),
                    __('Ale nan « Jere manm yo » epi peze ikòn retire bò kote non manm nan.'),
                    __('Apre jenere kalandriye a, ou pa ka retire manm san relanse yon nouvo sik.'),
                ],
                'actions' => [[__('Wè sòl mwen yo'), route('tontines.index')]],
                'related' => ['add_member', 'restart_round'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'notifications' => [
                'text' => [
                    __('LajanAnsanm voye notifikasyon nan aplikasyon an ak pa imel lè gen peman nouvo, vèsman, oswa lè yon dat limit pwoche.'),
                    __('Ou ka aktive notifikasyon push (Push Notification) ki ap parèt sou ekran telefòn ou menm si aplikasyon an fèmen.'),
                    __('Klike sou klòch ki nan tèt paj la pou wè tout notifikasyon ou yo.'),
                ],
                'actions' => [[__('Wè notifikasyon'), route('notifications.index')]],
                'related' => ['late', 'payout'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'forgot_password' => [
                'text' => [
                    __('Si w bliye modpas ou, klike sou « Modpas bliye? » nan paj koneksyon an.'),
                    __('Mete adrès imel ou, epi n ap voye yon lyen pou rekipere kont ou.'),
                    __('Verifye nan dosye « Spam » ou si w pa wè imel la.'),
                ],
                'actions' => [[__('Rekipere modpas'), route('password.request')]],
                'related' => ['platform_info'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'export_data' => [
                'text' => [
                    __('Ou ka ekspòte done yon sòl la dosye Excel (CSV) depi paj « Rapò yo » nan sòl la.'),
                    __('Ekspòtasyon CSV disponib sèlman sou plan Premium. Plan Gratis la ofri rapò PDF gratis.'),
                    __('Ou ka enprime nenpòt rapò an PDF san yon plan peyan.'),
                ],
                'actions' => [[__('Wè plan yo'), route('plans')]],
                'related' => ['reports', 'plans'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'whatsapp' => [
                'text' => [
                    __('WhatsApp sèvi kòm kanal kominikasyon prensipal pou rapèl ak notifikasyon manm.'),
                    __('Lè ou peze « Voye rapèl » pou yon manm an reta, aplikasyon an ouvri WhatsApp ak yon mesaj poli ki prè pou voye.'),
                    __('Pou sa mache, nimewo telefòn manm nan dwe ak kòd peyi Ayiti (+509) epi li dwe gen WhatsApp.'),
                ],
                'actions' => [[__('Wè sòl mwen yo'), route('tontines.index')]],
                'related' => ['late', 'add_member'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'free_plan_limits' => [
                'text' => [
                    __('Plan Gratis la pèmèt ou jere yon (1) sòl ki gen jiska 12 manm.'),
                    __('Si ou depase limit sa yo, fonksyon yo pase an lekti sèlman jiskaske ou renouvle yon plan peyan.'),
                    __('Plan Premium yo ofri plis sòl, plis manm, ekspòtasyon CSV, rapèl WhatsApp, ak kòmisyon otomatik.'),
                ],
                'actions' => [[__('Wè plan yo'), route('plans')], [__('Abònman mwen'), route('subscription')]],
                'related' => ['plans', 'readonly', 'grace'],
                'tone' => 'info',
                'is_personalized' => false,
            ],

            'grace_period' => [
                'text' => [
                    __('Lè abònman ou ekspire, ou antre nan yon peryòd gras kèk jou. Pandan tan sa a, ou kenbe tout avantaj plan ou an.'),
                    __('Yon banyè parèt pou ou enfòme konbyen jou ou rete.'),
                    __('Apre peryòd gras la, ou tounen sou plan Gratis. Anyen pa efase, men sa ki depase limit la pase an lekti sèlman.'),
                ],
                'actions' => [[__('Abònman mwen'), route('subscription')], [__('Wè plan yo'), route('plans')]],
                'related' => ['readonly', 'plans'],
                'tone' => 'info',
                'is_personalized' => false,
            ],

            'readonly_mode' => [
                'text' => [
                    __('Yon sòl pase an « lekti sèlman » lè abònman ou fini epi li depase limit plan Gratis la.'),
                    __('Ou ka toujou wè tout done yo ak rapò yo, men ou pa ka ajoute manm, chanje lòd, oswa jenere yon nouvo kalandriye.'),
                    __('Renouvle abònman ou pou jwenn aksè konplè ankò.'),
                ],
                'actions' => [[__('Abònman mwen'), route('subscription')], [__('Wè plan yo'), route('plans')]],
                'related' => ['grace', 'plans'],
                'tone' => 'alert',
                'is_personalized' => false,
            ],

            'declare_received' => [
                'text' => [
                    __('Lè òganizatè a voye lajan tou ou a, yon kat konfimasyon parèt sou paj espas manm ou a.'),
                    __('Si ou te resevwa lajan an, peze « Mwen resevwa lajan an ». Si non, peze « Mwen pa resevwa l » epi ekri sa ki pase.'),
                    __('Toujou verifye MonCash, NatCash, oswa labank ou anvan ou reponn.'),
                ],
                'actions' => [],
                'related' => ['payout', 'member_portal'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'payment_methods' => [
                'text' => [
                    __('Manm yo ka voye peman pa MonCash, NatCash, transfè labank (Sogebank, Unibank, BUH, BNC) oswa men nan men.'),
                    __('Òganizatè a defini ki kont pou manm yo peye. Manm deklare peman an ak nimewo tranzaksyon SMS yo resevwa.'),
                    __('LajanAnsanm pa touche ni kenbe lajan. Tout peman fèt dirèkteman ant manm yo ak òganizatè a.'),
                ],
                'actions' => [[__('Wè sòl mwen yo'), route('tontines.index')]],
                'related' => ['security', 'declare', 'cancel_payment'],
                'tone' => 'normal',
                'is_personalized' => false,
            ],

            'two_factor' => [
                'text' => [
                    __('LajanAnsanm pwoteje kont ou ak yon sistema koneksyon sekirize.'),
                    __('Pou plis sekirite, asire w ke modpas ou solid epi ou pa pataje li ak pèsonn.'),
                    __('Si ou sispèk kèk moun antre nan kont ou, chanje modpas ou imedyatman epi kontakte sipò a.'),
                ],
                'actions' => [[__('Rekipere modpas'), route('password.request')]],
                'related' => ['security', 'forgot_password'],
                'tone' => 'alert',
                'is_personalized' => false,
            ],

            default => self::fallbackResponse($intent, $audience),
        };
    }

    private static function findBestFaq(string $norm, string $audience): ?array
    {
        $faqs = HelpCenter::for($audience);
        $tokens = array_filter(explode(' ', $norm), fn ($t) => mb_strlen($t) >= 3);
        if (empty($tokens)) {
            return null;
        }

        $top = null;
        $maxScore = 0;

        foreach ($faqs as $faq) {
            $hay = self::normalize($faq['q'] . ' ' . ($faq['kw'] ?? ''));
            $score = 0;
            foreach ($tokens as $t) {
                if (str_contains($hay, $t)) {
                    $score++;
                }
            }

            if ($score > $maxScore) {
                $maxScore = $score;
                $top = $faq;
            }
        }

        return $top ? ['faq' => $top, 'score' => $maxScore] : null;
    }

    private static function fallbackResponse(string $query, string $audience): array
    {
        $wa = (string) config('lajanansanm.support_whatsapp');
        $cleanWa = preg_replace('/\D/', '', $wa);

        $actions = [];
        if ($audience === 'creator') {
            $actions[] = [__('Wè sòl mwen yo'), route('tontines.index')];
            $actions[] = [__('Wè plan yo'), route('plans')];
        } elseif ($audience === 'admin') {
            $actions[] = [__('Tablo bò'), route('admin.overview')];
        } else {
            $actions[] = [__('Paj Akèy'), url('/')];
        }

        if ($cleanWa !== '') {
            $actions[] = [__('Pale ak sipò sou WhatsApp'), 'https://wa.me/' . $cleanWa];
        }

        $topicsHint = $audience === 'creator'
            ? __('kreye sòl, ajoute manm, anrejistre peman, vèse lajan, rapèl WhatsApp, rapò, plan Premium...')
            : ($audience === 'member'
                ? __('deklare peman, kalandriye, lyen pèsonèl, konfime resepsyon...')
                : __('validate abonnman, jere itilizatè, estatistik platfòm...'));

        return [
            'text' => [
                __('Mwen konprann ou ap poze sou « :query ». Mwen paka reponn dirèkteman, men men kèk sijè mwen kouvri :', ['query' => mb_substr($query, 0, 60)]),
                $topicsHint,
                __('Eseye reformile kesyon ou an kreyòl oswa an franse, oswa klike sou yon sijè anba a.'),
            ],
            'actions' => $actions,
            'related' => $audience === 'creator'
                ? ['add_member', 'payout', 'plans', 'late', 'reports']
                : ['declare', 'txn', 'received', 'calendar'],
            'tone' => 'info',
            'is_personalized' => false,
        ];
    }

    private static function matchesAny(string $norm, array $needles): bool
    {
        foreach ($needles as $n) {
            $needleNorm = self::normalize($n);
            if (str_contains($norm, $needleNorm)) {
                return true;
            }
        }

        return false;
    }
}
