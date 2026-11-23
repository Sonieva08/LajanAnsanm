<?php

namespace App\Support;

/**
 * Réponses d'aide PRÉPARÉES à l'avance (aucune IA, aucune invention).
 * Quand une règle de l'application change, on change la réponse ici.
 */
final class HelpCenter
{
    public static function for(string $audience): array
    {
        $items = match ($audience) {
            'member' => self::member(),
            'guest' => self::guest(),
            'admin' => self::admin(),
            default => self::creator(),
        };
        $items = array_merge(self::concepts(), $items);

        if ($audience === 'guest') {
            foreach ($items as &$item) {
                if ($item['id'] === 'what_is') {
                    $item['actions'] = [[__('Kreye yon kont gratis'), route('register')], [__('Wè plan yo'), route('plans')]];
                    break;
                }
            }
            unset($item);
        }

        return array_values(array_map(fn (array $i) => $i + ['tone' => 'normal', 'actions' => [], 'related' => []], $items));
    }

    public static function find(string $audience, ?string $id): ?array
    {
        foreach (self::for($audience) as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    private static function concepts(): array
    {
        return [
            ['id' => 'tontine_basics', 'icon' => 'groups', 'q' => __('Kisa yon sòl ye?'), 'kw' => 'tontin tontine tontina sòl sol definisyon kisa ye egzakteman exactly',
                'a' => [__('Yon sòl (yo rele l « sòl » tou) se yon gwoup moun ki dakò pou chak moun kontribye yon montan regilyèman.'), __('Nan chak tou, kontribisyon yo rasanble pou youn nan manm yo resevwa pa l. Manm yo kontinye konsa dapre lòd ak kalandriye gwoup la te dakò sou yo.')],
                'related' => ['what_is']],
            ['id' => 'what_is', 'icon' => 'info', 'q' => __('Kisa LajanAnsanm ye?'), 'kw' => 'lajan ansanm lajanansanm lajanansanm lajan ansamn lajan an sanm lajanansnanm lajanasnanm aplikasyon platfòm',
                'a' => [__('LajanAnsanm se yon aplikasyon entènèt ki ede òganizatè yo kreye epi jere sòl: manm, kalandriye tou, kontribisyon, peman ak rapò.'), __('Manm yo ka itilize yon lyen pèsonèl pou wè kalandriye yo epi deklare peman yo. LajanAnsanm se yon zouti jesyon: li pa resevwa ni kenbe lajan sòl la.')],
                'related' => ['tontine_basics']],
        ];
    }

    private static function creator(): array
    {
        return [
            // ── Kreye & Jere Sòl (kat. 2) ────────────────────────────────────────
            ['id' => 'how_to_start', 'icon' => 'play_circle', 'q' => __('Kijan pou mwen kreye premye sòl mwen an?'), 'kw' => 'kreye premye sòl sol tontin kòmanse',
                'a' => [__('Klike sou "Kreye yon sòl", bay li yon non, mete kantite lajan kotizasyon an, chwazi peryòd la, epi ajoute manm ou yo!'), __('W ap mete non sòl la, montan chak moun dwe peye, frekans lan (chak semèn oswa chak mwa), ak dat premye peman an.')],
                'actions' => [[__('Kreye yon sòl kounye a'), route('tontines.create')]], 'related' => ['add_member', 'order']],
            ['id' => 'tontine_types', 'icon' => 'swap_horiz', 'q' => __('Konbyen kalite sòl mwen ka kreye?'), 'kw' => 'kalite sòl semèn mwa frekans',
                'a' => [__('Ou ka kreye sòl chak semèn oswa chak mwa — se ou ki chwazi!'), __('Ou ka gen plizyè sòl diferan ak menm kont ou a.')]],
            ['id' => 'max_members', 'icon' => 'groups', 'q' => __('Konbyen manm mwen ka mete nan yon sòl maksimòm?'), 'kw' => 'maksimòm manm limit kantite 30',
                'a' => [__('Ou ka jiska 30 manm nan yon sèl sòl.')]],
            ['id' => 'edit_tontine', 'icon' => 'edit', 'q' => __('Èske mwen ka chanje non sòl la apre mwen kreye l?'), 'kw' => 'chanje non sòl modifye apre kreye paramèt',
                'a' => [__('Wi, ou ka modifye non sòl la nan paramèt gwoup la.'), __('Ou ka modifye non, montan, oswa frekans yon sòl si kalandriye a poko jenere epi li toujou nan faz preparasyon.'), __('Depi ou jenere kalandriye a, pifò paramèt yo vin bloke pou pwoteje konfyans manm yo.')]],
            ['id' => 'contribution_setup', 'icon' => 'payments', 'q' => __('Kijan pou mwen mete kantite lajan kotizasyon an?'), 'kw' => 'kantite lajan kotizasyon mete espas kreye',
                'a' => [__('Lè w ap kreye sòl la, gen yon espas pou ou ekri konbyen lajan chak moun ap peye.')]],
            ['id' => 'multiple_tontines', 'icon' => 'library_add', 'q' => __('Èske mwen ka gen plizyè sòl an menm tan?'), 'kw' => 'plizyè sòl menm tan ansanm jere',
                'a' => [__('Byen wi! Ou ka jere plizyè sòl diferan ak menm kont ou a.'), __('Lè w konekte, paj dakèy la montre tout sòl kote w se manm oswa òganizatè.')]],
            ['id' => 'frequency_setup', 'icon' => 'event_repeat', 'q' => __('Kijan pou mwen mete peryòd pman: chak semèn oswa chak mwa?'), 'kw' => 'peryòd pman semèn mwa opsyon chwazi',
                'a' => [__('Lè w ap kreye sòl la, w ap wè opsyon pou chwazi ant chak semèn ak chak mwa.')]],
            ['id' => 'tontine_privacy', 'icon' => 'lock', 'q' => __('Èske lòt moun ka wè sòl mwen yo?'), 'kw' => 'prive vizib sòl lòt moun wè',
                'a' => [__('Non! Sèlman manm ki nan gwoup la ka wè sòl sa a.'), __('Wi, tout sòl yo priz pa defo — se sèlman moun ou envite ki ka antre.')]],
            ['id' => 'change_organizer', 'icon' => 'manage_accounts', 'q' => __('Kijan pou mwen chanje ki moun ki òganizatè yon sòl?'), 'kw' => 'chanje òganizatè moun lis fè transfere',
                'a' => [__('Nan lis manm yo, chwazi moun nan epi chwazi "Fè l òganizatè".'), __('Kounye a, se yon sèl òganizatè pou chak sòl.')]],
            ['id' => 'tontine_description', 'icon' => 'description', 'q' => __('Kijan pou mwen mete yon deskripsyon sou sòl mwen an?'), 'kw' => 'deskripsyon sòl mete zòn ekri',
                'a' => [__('Lè w ap kreye sòl la oswa nan paramèt li yo, gen yon zòn pou ekri yon ti deskripsyon.')]],
            ['id' => 'tontine_end', 'icon' => 'event', 'q' => __('Èske mwen ka mete yon dat fen otomatik pou sòl la?'), 'kw' => 'dat fen otomatik fini sòl endike',
                'a' => [__('Sòl la fini otomatikman lè tout moun fin pran tou yo. Ou kapab tou endike yon dat fen si w vle.')]],
            ['id' => 'suspend_tontine', 'icon' => 'pause_circle', 'tone' => 'alert', 'q' => __('Kijan pou mwen sispann yon sòl ki deja kòmanse?'), 'kw' => 'sispann sòl paramèt avèti manm',
                'a' => [__('Nan paramèt sòl la, chwazi "Sispann sòl la". Sonje avèti tout manm yo davans!'), __('Wi, osi lontan li poko efase nèt, ou ka rekòmanse l.')]],
            ['id' => 'delete_tontine', 'icon' => 'delete_forever', 'tone' => 'alert', 'q' => __('Kijan pou mwen efase yon sòl nèt?'), 'kw' => 'efase sòl nèt retire manm elimine',
                'a' => [__('Premyèman retire tout manm yo, answit nan paramèt sòl la chwazi "Efase sòl nèt".'), __('⚠️ Efase yon sòl retire tout done li ki gen ladan l, manm yo ak kalandriye a nèt.')],
                'actions' => [[__('Wè sòl mwen yo'), route('tontines.index')]], 'related' => ['add_member', 'readonly']],
            // ── Envitasyon & Manm (kat. 3) ────────────────────────────────────
            ['id' => 'add_member', 'icon' => 'group_add', 'q' => __('Ki jan pou m ajoute yon manm?'), 'kw' => 'manm ajoute nouvo moun patisipan telefon envite',
                'a' => [__('Kopie lyen pèsonèl sòl la epi voye l bay moun yo pa WhatsApp, mesaj oswa nenpòt kote!'), __('Ou ka ajoute manm sèlman anvan ou jenere kalandriye a. Kantite manm lan limite pa plan ou an.'), __('Wi, ou ka ajoute nouvo manm nenpòt ki lè, men w ap bezwen mete l nan fen lis la.')],
                'actions' => [[__('Wè sòl mwen yo'), route('tontines.index')]], 'related' => ['order', 'readonly']],
            ['id' => 'invitation_link', 'icon' => 'link', 'q' => __('Kijan lyen pèsonèl chak manm mache?'), 'kw' => 'lyen pèsonèl envitasyon manm inik',
                'a' => [__('Chak manm gen yon lyen inik. Lè l klike sou li, li wè pwòp peman l ak kalandriye a.'), __('Non, menm lyen an rete pou tout dire sòl la.')]],
            ['id' => 'invitation_status', 'icon' => 'pending', 'q' => __('Kijan pou mwen wè ki moun ki aksepte envitasyon an deja?'), 'kw' => 'aksepte envitasyon estati aktif tann wè',
                'a' => [__('Nan lis manm yo, w ap wè estati chak moun: "An ap tann" oswa "Manm aktif".')]],
            ['id' => 'remove_member', 'icon' => 'person_remove', 'tone' => 'alert', 'q' => __('Kijan pou mwen retire yon manm nan sòl la?'), 'kw' => 'retire manm efase sòl twa pwen',
                'a' => [__('Nan lis manm yo, klike sou twa pwen bò kote non l, epi chwazi "Retire nan gwoup la".'), __('Si li te deja peye, li ap toujou gen dwa pou li resevwa lajan l daprè lòd li te ye a.')],
                'related' => ['add_member', 'restart']],
            ['id' => 'order', 'icon' => 'swap_vert', 'q' => __('Ki jan lòd resepsyon an fèt?'), 'kw' => 'lod ordre tou tiraj aza sò pozisyon benefisyè chanje',
                'a' => [__('Se gwoup la ki deside lòd la ansanm, epi òganizatè a mete l nan sistèm lan.'), __('Manm ki nan pozisyon 1 resevwa nan tou 1, pozisyon 2 nan tou 2, elatriye.'), __('Ou ka monte oswa desann manm yo oswa fè yon tiraj o sò. Lè ou jenere kalandriye a, lòd la bloke.')],
                'related' => ['add_member']],
            ['id' => 'send_reminder', 'icon' => 'send', 'q' => __('Kijan pou mwen voye rapèl bay yon manm pat vini ankò?'), 'kw' => 'rapèl voye manm reta mesaj whatsapp',
                'a' => [__('Ou ka voye yon mesaj dirèkteman nan platfòm la oswa kopie lyen an epi voye l ankò ba li.'), __('Ak yon plan ki gen rapèl WhatsApp, bouton « Voye rapèl » louvri WhatsApp ak yon mesaj poli ki prè pou voye.')]],
            ['id' => 'cancel_invitation', 'icon' => 'cancel', 'q' => __('Kijan pou mwen anile yon envitasyon mwen te voye deja?'), 'kw' => 'anile envitasyon pa aksepte lis',
                'a' => [__('Si moun nan pat aksepte toujou, ou ka anile envitasyon an nan lis envitasyon yo.')]],
            // ── Peman kreye (kat. 4) ────────────────────────────────────────────
            ['id' => 'declare_notified', 'icon' => 'notifications', 'q' => __('Kijan òganizatè a konnen mwen deja peye?'), 'kw' => 'òganizatè konnen peye notifikasyon imedyatman valide',
                'a' => [__('Lè w deklare pman w lan, òganizatè a resevwa yon notifikasyon imedyatman. Li kapab valide l tou bò kote l.')]],
            ['id' => 'correct_payment', 'icon' => 'edit', 'q' => __('Kijan pou mwen korije yon pman mwen te antre mal?'), 'kw' => 'korije pman erè anile modifye antre mal',
                'a' => [__('Kontakte òganizatè a pou li anile ansyen deklareman an epi ou ka fè yon nouvo.')]],
            ['id' => 'manage_late', 'icon' => 'schedule', 'tone' => 'alert', 'q' => __('Kijan pou m jere yon manm ki pa peye alè?'), 'kw' => 'manm pa peye alè rapèl jere reta',
                'a' => [__('Ou ka voye yon rapèl otomatikman nan platfòm la, oswa pale ak li dirèkteman.'), __('Ou ka toujou anrejistre peman yon manm menm si li te an reta san okenn penalite sistèm.')],
                'related' => ['late', 'payout']],
            // ── Sekirite & Konfidansyalite (kat. 6) ─────────────────────────────
            ['id' => 'readonly', 'icon' => 'visibility', 'tone' => 'alert', 'q' => __('Poukisa sòl mwen an lekti sèlman?'), 'kw' => 'lekti sèlman lekti bloke modifye abònman limit gratis',
                'a' => [__('Sa rive lè abònman ou fini epi sòl la depase limit plan gratis la.'), __('Ou ka toujou wè li, wè rapò yo epi efase li, men ou pa ka modifye li.'), __('Renouvle abònman ou oswa chwazi yon plan pou modifye li ankò.')],
                'actions' => [[__('Abònman mwen'), route('subscription')], [__('Wè plan yo'), route('plans')]], 'related' => ['grace', 'plans']],
            ['id' => 'late', 'icon' => 'schedule', 'tone' => 'alert', 'q' => __('Kisa pou m fè si yon manm pa peye nan dat la?'), 'kw' => 'reta pa peye rapèl whatsapp retard dat limit',
                'a' => [__('Yon manm vin « An reta » nan jou ki swiv dat limit la. Ou ka toujou anrejistre peman li.'), __('Ak yon plan ki gen rapèl WhatsApp, bouton « Voye rapèl » louvri WhatsApp ak yon mesaj pare.')],
                'related' => ['payout']],
            // ── Vèsman & Benefisyè ────────────────────────────────────────────
            ['id' => 'payout', 'icon' => 'payments', 'q' => __('Kijan pou m vèse lajan an bay benefisyè a?'), 'kw' => 'vèse vèsman benefisyè lajan peye tou fèmen',
                'a' => [__('Louvri « Vèsman yo » epi chwazi bouton « Vèse » sou tou ki ouvè a. Fòk tou a gen yon benefisyè ak omwen yon kontribisyon; san sa, ou pa ka fè vèsman an.'), __('Si gen manm ki poko peye, ou ka tann oswa konfime ou vle vèse montan ki deja ranmase a sèlman. Montan nèt la se kontribisyon ki antre yo, mwens kòmisyon ki aplikab.'), __('Fè transfè a deyò LajanAnsanm, epi anrejistre metòd, kont moun ki voye a ak nimewo tranzaksyon an. Resi an imaj opsyonèl. Vèsman an fèmen tou a epi ouvri tou swivan an.')],
                'related' => ['payout_proof', 'payout_problem', 'commission', 'cancel_payment']],
            ['id' => 'payout_proof', 'icon' => 'receipt_long', 'q' => __('Ki enfòmasyon pou m mete lè m fin voye vèsman an?'), 'kw' => 'resi prèv transfè nimewo tranzaksyon kont voye metòd voye detay',
                'a' => [__('Apre ou fin voye lajan an, antre metòd ou te itilize a, nimewo/kont ki te voye lajan an ak nimewo tranzaksyon an. Nimewo sa yo ede verifye transfè a.'), __('Ou ka ajoute yon nòt ak foto resi a (JPEG, PNG oswa WebP, jiska 5 MB). LajanAnsanm anrejistre transfè a; li pa voye lajan an pou ou.')],
                'related' => ['payout', 'payout_problem']],
            ['id' => 'payout_problem', 'icon' => 'report', 'tone' => 'alert', 'q' => __('Kisa pou m fè si benefisyè a di li pa resevwa vèsman an?'), 'kw' => 'benefisyè pa resevwa lajan plent diskisyon pwoblèm transfè verifye renvoye',
                'a' => [__('Verifye detay tranzaksyon an ak resi a, epi pale ak benefisyè a pou chèche konnen sa k pase. Pa voye yon lòt fwa san ou pa verifye premye transfè a.'), __('Benefisyè a ka rapòte pwoblèm nan nan espas manm li. Apre ou fin verifye oswa regle sa, ou ka mande l konfime ankò; si li te resevwa lajan an, li ka konfime nan espas li.')],
                'related' => ['payout', 'payout_proof']],
            ['id' => 'cancel_payment', 'icon' => 'undo', 'q' => __('Mwen anrejistre yon peman pa erè. Kijan pou m anile l?'), 'kw' => 'anile peman erè efase korije',
                'a' => [__('Nan paj « Peman yo », peze ✕ bò peman an. Sa posib sèlman pandan tou a ouvè.'), __('Apre vèsman an, peman an pa ka anile ankò.')],
                'related' => ['payout']],
            ['id' => 'restart', 'icon' => 'autorenew', 'q' => __('Kijan pou m relanse yon nouvo sik?'), 'kw' => 'relanse nouvo sik cycle recommencer kalandriye fini',
                'a' => [__('Lè tout tou yo fini, ale nan « Kalandriye tou yo », chwazi dat la, answit peze « Relanse yon nouvo sik ».'), __('Ou ka ajoute oswa retire manm anvan ou jenere nouvo kalandriye a. Sa mande yon plan ki gen fonksyon sa a.')],
                'related' => ['plans', 'add_member']],
            // ── Plans & Abònman ────────────────────────────────────────────────
            ['id' => 'plans', 'icon' => 'workspace_premium', 'q' => __('Kijan pou m peye yon plan Premium?'), 'kw' => 'premium plan abònman peye moncash natcash chanje',
                'a' => [__('Ale nan « Abònman mwen », peze « Wè plan yo », epi chwazi yon plan ak yon peryòd.'), __('Voye lajan an sou nimewo ki afiche a, mete kòd referans lan nan nòt la, epi deklare peman an.'), __('Nou verifye peman an epi nou aktive plan an.'), __('Ou ka peye abònman w dirèkteman sou sit la via MonCash, NatCash oswa transfè labank.')],
                'actions' => [[__('Wè plan yo'), route('plans')]], 'related' => ['grace']],
            ['id' => 'grace', 'icon' => 'hourglass_bottom', 'q' => __('Kisa k pase lè abònman mwen fini?'), 'kw' => 'abònman fini ekspire gras renouvle expire',
                'a' => [__('Ou gen kèk jou gras: ou kenbe tout avantaj yo epi yon banyè di w konbyen jou ki rete.'), __('Apre sa, ou tounen sou plan gratis la. Anyen pa efase: sa ki depase limit gratis la pase an lekti sèlman.'), __('Ou ka anile abònman w — kont ou ap vin tounen vèsyon gratis la lè peryòd ou te peye a fini.')],
                'actions' => [[__('Abònman mwen'), route('subscription')]], 'related' => ['readonly']],
            ['id' => 'subscription_info', 'icon' => 'info', 'q' => __('Kijan pou mwen wè enfòmasyon sou abònman mwen an?'), 'kw' => 'abònman enfòmasyon wè paramèt',
                'a' => [__('Ale nan paramèt kont ou a → Abònman — tout enfòmasyon yo la klè.')]],
            // ── Rapò ──────────────────────────────────────────────────────────
            ['id' => 'reports', 'icon' => 'description', 'q' => __('Ki kote pou m jwenn rapò yo?'), 'kw' => 'rapò rapo pdf enprime csv excel ekspòte',
                'a' => [__('Louvri sòl la epi peze « Rapò yo ». Gen yon rapò pa tou ak yon rapò pa sik.'), __('Ou ka enprime l (PDF) gratis. Ekspòte nan Excel (CSV) mande yon plan ki gen fonksyon sa a.')]],
            ['id' => 'portal', 'icon' => 'groups', 'q' => __('Kijan espas manm nan fonksyone?'), 'kw' => 'espas manm pòtay portal lyen deklarasyon manm',
                'a' => [__('Nan paj sòl la, ou ka louvri yon espas pou manm yo epi voye chak manm lyen pèsonèl li.'), __('Manm nan wè kalandriye a epi deklare peman li. Ou verifye deklarasyon an epi ou aksepte oswa rejte l.'), __('Ou menm ou jwenn lajan an dirèkteman. LajanAnsanm pa jwenn lajan an.')],
                'related' => ['late']],
            ['id' => 'commission', 'icon' => 'percent', 'q' => __('Kijan kòmisyon òganizatè a mache?'), 'kw' => 'kòmisyon komisyon pousantaj òganizatè retni',
                'a' => [__('Ou fikse yon pousantaj lè ou kreye sòl la. Li soustrè sou lajan benefisyè a resevwa a chak tou.'), __('Pousantaj la bloke lè ou jenere kalandriye a. Fonksyon sa a mande yon plan ki gen li.')],
                'related' => ['payout']],
        ];
    }

    private static function member(): array
    {
        return [
            // ── Peman & Kotizasyon (kat. 4) ──────────────────────────────────────
            ['id' => 'declare', 'icon' => 'payments', 'q' => __('Kijan pou m deklare peman m?'), 'kw' => 'deklare peman peye voye lajan mwen peye moncash natcash',
                'a' => [__('Lè tou a ouvè, gade montan kontribisyon an ak enfòmasyon peman òganizatè a bay. Fè transfè a dirèkteman sou MonCash, NatCash oswa kont labank ki parèt; LajanAnsanm pa resevwa lajan an.'), __('Apre peman an, sou lyen pèsonèl ou a peze « Mwen peye », chwazi metòd la, antre nimewo ou te peye avè l la ak nimewo tranzaksyon an. Ou ka ajoute resi an imaj ak yon nòt.'), __('Voye deklarasyon an. Li pa konte kòm kontribisyon jiskaske òganizatè a verifye peman an epi apwouve li.')],
                'related' => ['contribution_amount', 'txn', 'wait', 'rejected']],
            ['id' => 'contribution_amount', 'icon' => 'payments', 'q' => __('Konbyen pou m peye pou kontribisyon an, epi ki kote pou m voye l?'), 'kw' => 'manm bay kontribisyon pal montan kantite kotizasyon kòb kob kont òganizatè',
                'a' => [__('Montan pou peye a parèt nan espas manm nan anba « Pou peye kounye a ». Enfòmasyon MonCash, NatCash oswa labank òganizatè a mete disponib la parèt tou.'), __('Voye montan an sou youn nan kont ki disponib yo. Si ou pa wè okenn kont oswa enfòmasyon an pa klè, kontakte òganizatè a anvan ou fè transfè a.')],
                'related' => ['declare']],
            ['id' => 'declaration_pending', 'icon' => 'hourglass_top', 'q' => __('Mwen voye deklarasyon an men kontribisyon an poko konte. Poukisa?'), 'kw' => 'deklarasyon an atant tann apwobasyon poko konte peman verifye',
                'a' => [__('Yon deklarasyon ki an atant poko konte kòm kontribisyon. Òganizatè a dwe verifye nan kont li si lajan an antre, epi apwouve deklarasyon an.'), __('Pa voye yon lòt deklarasyon pou menm peman an pandan premye a an atant. Si verifikasyon an pran tan, kontakte òganizatè a dirèkteman.')],
                'related' => ['declare', 'wait']],
            ['id' => 'txn', 'icon' => 'tag', 'q' => __('Ki kote pou m jwenn nimewo tranzaksyon an?'), 'kw' => 'nimewo tranzaksyon kòd sms resi referans',
                'a' => [__('Se nimewo ou wè nan SMS konfimasyon MonCash oswa NatCash ou apre transfè a.'), __('Kopye l egzakteman, san espas anplis.')],
                'related' => ['declare']],
            ['id' => 'rejected', 'icon' => 'error', 'tone' => 'alert', 'q' => __('Deklarasyon mwen rejte. Kisa pou m fè?'), 'kw' => 'rejte rejè refize deklarasyon korije',
                'a' => [__('Gade rezon òganizatè a bay la sou paj ou a. Kontribisyon an poko konte kòm peye.'), __('Peze « Modifye epi voye ankò », korije detay peman an oswa resi a, epi voye deklarasyon an ankò.'), __('Si ou pa konprann rezon an oswa lajan an deja soti nan kont ou, pale ak òganizatè a pou verifye peman an.')],
                'related' => ['txn', 'declare', 'wait']],
            ['id' => 'wait', 'icon' => 'hourglass_top', 'q' => __('Konbyen tan pou deklarasyon mwen verifye?'), 'kw' => 'tann tan verifye konfimasyon pran tan',
                'a' => [__('Se òganizatè sòl la ki verifye l, kidonk pa gen yon tan fiks.'), __('Si sa pran twòp tan, pale ak li dirèkteman.')]],
            ['id' => 'payment_history', 'icon' => 'history', 'q' => __('Kijan pou mwen wè istwa tout pman mwen yo?'), 'kw' => 'istwa pman mwen wè dat fason',
                'a' => [__('Ale nan "Istwa pman mwen yo" — w ap wè chak pman ak dat li ak fason ou te peye.'), __('Wi, ou ka ekspòte istwa w an fòma PDF oswa CSV.')]],
            ['id' => 'late_payment', 'icon' => 'schedule', 'tone' => 'alert', 'q' => __('Kisa k rive si mwen pa peye nan dat ki te fikse a?'), 'kw' => 'pa peye dat reta penalite gwoup frè',
                'a' => [__('Non pa pèdi, men gwoup la ap wè w an reta. Pale ak òganizatè a pou w jwenn yon solisyon ansanm.'), __('Non! LajanAnsanm pa mande okenn frè pou pman anreta. Se gwoup la ki deside ansanm si yo vle yon aranjman.')]],
            ['id' => 'declare_cash', 'icon' => 'money', 'q' => __('Kijan pou mwen konfime yon pman an kach?'), 'kw' => 'kach men nan men konfime make peye òganizatè',
                'a' => [__('Lè w peye an kach, di òganizatè a, epi li make li kòm "Peye" sou platfòm la.')],
                'related' => ['declare']],
            ['id' => 'payment_visibility', 'icon' => 'visibility', 'q' => __('Èske lòt manm wè konbyen mwen peye chak fwa?'), 'kw' => 'lòt manm wè konbyen pman prive',
                'a' => [__('Tout moun konnen kantite kotizasyon an jeneral, men sèlman ou menm ak òganizatè a wè lè ou peye egzakteman.')]],
            // ── Orè & Benefisyè (kat. 5) ─────────────────────────────────────
            ['id' => 'received', 'icon' => 'verified', 'q' => __('Kijan pou m konfime mwen resevwa lajan m?'), 'kw' => 'resevwa lajan tou mwen resevwa konfime pa resevwa',
                'a' => [__('Lè òganizatè a voye lajan tou ou a, yon kat parèt sou paj ou a pou ou konfime ou resevwa l.'), __('Si ou jwenn lajan an, peze « Mwen resevwa lajan an ». Si ou pa jwenn li, peze « Mwen pa resevwa l » epi di sa k pase a.'), __('Verifye nan MonCash, NatCash oswa labank ou anvan ou reponn.')],
                'related' => ['destination']],
            ['id' => 'destination', 'icon' => 'account_balance_wallet', 'q' => __('Kijan pou m di ki jan m vle resevwa lajan m?'), 'kw' => 'resevwa kont kote lajan an moncash natcash labank kach',
                'a' => [__('Sou paj ou a, nan « Kijan ou vle resevwa lajan ou? », chwazi MonCash, NatCash, labank oswa men nan men.'), __('Ou ka modifye l nenpòt kilè anvan òganizatè a vèse tou ou a.')],
                'related' => ['received']],
            ['id' => 'calendar', 'icon' => 'calendar_month', 'q' => __('Kote pou m wè kalandriye a?'), 'kw' => 'kalandriye tou dat limit ki lè resevwa',
                'a' => [__('Paj ou a montre kalandriye tou yo ak dat limit yo.'), __('Si li poko parèt, sa vle di òganizatè a poko jenere l.')]],
            ['id' => 'payout_notification', 'icon' => 'notifications', 'q' => __('Konbyen jou avan mwen ap resevwa yon notifikasyon se tou mwen?'), 'kw' => 'notifikasyon jou avan tou mwen alèt',
                'a' => [__('Ou ap resevwa yon notifikasyon 2 jou avan jou ou ap resevwa lajan an.'), __('Wi! Ou ap resevwa yon rapèl yon jou avan jou pman an tou.')]],
            ['id' => 'payout_amount', 'icon' => 'payments', 'q' => __('Kijan pou mwen konnen konbyen lajan m ap touche?'), 'kw' => 'konbyen lajan touche montan tou kantite',
                'a' => [__('Kantite lajan an ekri klè sou paj sòl la — se menm kantite ak sa w ap peye chak peryòd.'), __('Wi, nan yon sòl nòmal, chak moun resevwa menm kantite lajan kotizasyon an.')]],
            ['id' => 'payout_not_received', 'icon' => 'report', 'tone' => 'alert', 'q' => __('Kisa mwen dwe fè si mwen pa resevwa lajan mwen nan dat la?'), 'kw' => 'pa resevwa lajan dat reta tcheke',
                'a' => [__('Tcheke ak òganizatè a oswa ak moun ki te dwe peye w la — pafwa gen ti reta ak metòd pman yo.')]],
            ['id' => 'tontine_progress', 'icon' => 'timeline', 'q' => __('Kisa k rive lè tout moun fin pran tou yo nan sòl la?'), 'kw' => 'fini tou sik fen nouvo',
                'a' => [__('Sik la fini! Ou ka kòmanse yon nouvo sòl si gwoup la vle kontinye ansanm.'), __('Nan tèt paj sòl la, w ap wè: "Peryòd X sou Y".')]],
            // ── Sekirite (kat. 6) ─────────────────────────────────────────────
            ['id' => 'link', 'icon' => 'link_off', 'tone' => 'alert', 'q' => __('Lyen mwen an pa mache ankò.'), 'kw' => 'lyen pa mache valab ekspire nouvo lyen',
                'a' => [__('Lyen pèsonèl ou a sispann mache si òganizatè a renouvle li oswa fèmen espas manm nan.'), __('Mande l yon nouvo lyen. Pa pataje lyen ou a ak pèsonn.')]],
            ['id' => 'member_visibility', 'icon' => 'groups', 'q' => __('Èske lòt moun ka wè non mwen nan lis manm yo?'), 'kw' => 'non vizib lis manm gwoup wè',
                'a' => [__('Wi, non w vizib pou tout manm nan menm sòl la — se nòmal pou tout moun konnen kiyès nan gwoup la.')]],
            ['id' => 'bank_data', 'icon' => 'credit_card_off', 'q' => __('Èske platfòm lan kenbe done kat labank mwen yo?'), 'kw' => 'kat labank done sere kenbe',
                'a' => [__('Non, nou pa mande ni nou pa sere enfòmasyon kat labank ou yo.')]],
        ];
    }

    private static function guest(): array
    {
        return [
            // ── Kòman pou kòmanse & Kont (kat. 1) ──────────────────────────────
            ['id' => 'how_to_start', 'icon' => 'play_circle', 'q' => __('Kijan pou m kòmanse?'), 'kw' => 'komanse kòmanse enskri kreye kont premye fwa',
                'a' => [__('Ale sou paj prensipal la, klike sou "Kreye yon kont", antre non w, nimewo w ak yon modpas. Ou fini!'), __('Apre sa, ou ka kreye premye sòl ou, chwazi montan chak tou, ajoute manm yo epi lanse kalandriye a.')],
                'actions' => [[__('Enskri kounye a'), route('register')]], 'related' => ['what_is', 'is_it_free']],
            ['id' => 'is_it_free', 'icon' => 'savings', 'q' => __('Èske kreye kont la gratis?'), 'kw' => 'gratis kont pri kob kòb debaz',
                'a' => [__('Wi, kreye yon kont se totalman gratis.'), __('Ou gen 3 mwa eseye platfòm lan san w pa peye anyen.')],
                'actions' => [[__('Wè plan yo'), route('plans')]], 'related' => ['holds_money', 'no_credit_card']],
            ['id' => 'no_credit_card', 'icon' => 'credit_card_off', 'q' => __('Èske mwen bezwen yon kat kredi pou m enskri?'), 'kw' => 'kat kredi enskri bezwen peman',
                'a' => [__('Non! Ou pa bezwen kat kredi pou enskri oswa pou w itilize peryòd eseye a.')]],
            ['id' => 'login_how', 'icon' => 'login', 'q' => __('Kijan mwen konekte sou kont mwen an?'), 'kw' => 'konekte kont nimewo modpas koneksyon',
                'a' => [__('Antre sou sit la, klike sou "Konekte", epi mete nimewo ou ak modpas ou.')],
                'actions' => [[__('Konekte'), route('login')]]],
            ['id' => 'forgot_password', 'icon' => 'lock_reset', 'q' => __('Kisa mwen dwe fè si mwen bliye modpas mwen?'), 'kw' => 'modpas bliye modifye retabli rekipere password',
                'a' => [__('Klike sou "Bliye modpas" sou paj koneksyon an, epi swiv etap yo pou kreye yon nouvo modpas.'), __('Wi, ou ka refè l ak nimewo telefòn ou sèlman.')],
                'actions' => [[__('Rekipere modpas'), route('password.request')]], 'related' => ['how_to_start']],
            ['id' => 'change_username', 'icon' => 'edit', 'q' => __('Èske mwen ka chanje non itilizatè mwen an?'), 'kw' => 'chanje non itilizatè paramèt kont',
                'a' => [__('Wi, ou ka chanje non w nan paramèt kont ou a.')]],
            ['id' => 'update_phone', 'icon' => 'phone', 'q' => __('Kijan pou mwen mete ajou nimewo telefòn mwen?'), 'kw' => 'nimewo telefòn ajou mete chanje enfòmasyon pèsonèl',
                'a' => [__('Ale nan paramèt kont ou a, chwazi "Enfòmasyon pèsonèl", epi mete nouvo nimewo a.')]],
            ['id' => 'multi_device', 'icon' => 'devices', 'q' => __('Èske mwen ka itilize menm kont lan sou plizyè telefòn?'), 'kw' => 'kont plizyè telefòn aparèy konekte',
                'a' => [__('Wi, ou ka konekte kont ou a sou plizyè aparèy.')]],
            ['id' => 'delete_account', 'icon' => 'delete_forever', 'tone' => 'alert', 'q' => __('Kijan mwen ka fèmen kont mwen an nèt?'), 'kw' => 'fèmen efase kont nèt done retire',
                'a' => [__('Ale nan paramèt kont ou a → Anviwònman → Efase kont mwen.'), __('Sonje ou p ap ka refè done yo apre sa.'), __('Non, yon fwa ou efase kont la nèt, li pa posib pou refè li. W ap bezwen kreye yon nouvo.'), __('Tout done pèsonèl ou yo ap efase nèt nan sistèm nou an.')]],
            ['id' => 'email_required', 'icon' => 'alternate_email', 'q' => __('Èske mwen bezwen yon adrès imèl pou kreye kont?'), 'kw' => 'imèl adrès bezwen kreye kont nimewo',
                'a' => [__('Non, yon nimewo telefòn se ase pou kreye kont ou.')]],
            ['id' => 'age_minimum', 'icon' => 'person', 'q' => __('Èske gen yon laj minimòm pou itilize LajanAnsanm?'), 'kw' => 'laj minimòm minè timoun 18 an',
                'a' => [__('Wi, ou dwe gen omwen 18 an. Non, timoun pa ka gen kont sou platfòm lan.')]],
            ['id' => 'one_account_per_phone', 'icon' => 'smartphone', 'q' => __('Konbyen kont mwen ka kreye ak menm nimewo a?'), 'kw' => 'nimewo kont kreye limit menm',
                'a' => [__('Yon sèl kont pou chak nimewo telefòn.')]],
            ['id' => 'verify_account', 'icon' => 'verified_user', 'q' => __('Kijan pou mwen verifye kont mwen an?'), 'kw' => 'verifye kont kòd valide enskri',
                'a' => [__('Lè w ap enskri, y ap voye yon kòd sou nimewo w — mete kòd sa a pou valide kont ou.')]],
            ['id' => 'account_hacked', 'icon' => 'report', 'tone' => 'alert', 'q' => __('Kisa mwen dwe fè si yon lòt moun itilize kont mwen?'), 'kw' => 'kont vòlè pèmisyon sekirize chanje modpas',
                'a' => [__('Chanje modpas ou imedyatman epi kontakte sipò nou sou WhatsApp pou nou ede w sekirize kont ou.')]],
            // ── Peman & Kotizasyon (kat. 4) ─────────────────────────────────────
            ['id' => 'holds_money', 'icon' => 'security', 'q' => __('Èske LajanAnsanm kenbe lajan an?'), 'kw' => 'kenbe lajan sekirite kob kont kote lajan an ye',
                'a' => [__('LajanAnsanm pa kenbe lajan w. Lajan an ale dirèkteman ant ou menm ak lòt manm yo.'), __('Nou ede w swiv peman yo; tout peman fèt dirèkteman ant manm yo ak òganizatè a pa MonCash, NatCash oswa labank.'), __('Aplikasyon an la pou jesyon, transparans ak kontwòl.')],
                'related' => ['is_it_free', 'payment_methods']],
            ['id' => 'payment_methods', 'icon' => 'account_balance', 'q' => __('Ki metòd pman ki aksepte sou platfòm lan?'), 'kw' => 'moncash natcash labank transfè kach mwayen peman aksepte',
                'a' => [__('MonCash, NatCash, lajan kach ak vireman labank — tout sa yo ap travay!'), __('Chak moun ka itilize metòd ki pi bon pou li.'), __('Lè w peye an kach, di òganizatè a, epi li make li kòm "Peye" sou platfòm la.')],
                'related' => ['holds_money']],
            // ── Sekirite (kat. 6) ────────────────────────────────────────────────
            ['id' => 'data_security', 'icon' => 'lock', 'q' => __('Èske done mwen yo an sekirite sou LajanAnsanm?'), 'kw' => 'done sekirite chiffre pwoteje konfidansyalite',
                'a' => [__('Wi, nou pran sekirite done yo oserye. Done ou yo chiffre epi pwoteje.'), __('Nou pa vann oswa pataje done pèsonèl ou yo ak pèsonn.')]],
            ['id' => 'password_phishing', 'icon' => 'report', 'tone' => 'alert', 'q' => __('Èske LajanAnsanm mande pou modpas mwen pa mesaj?'), 'kw' => 'modpas mesaj mande phishing imèl telefòn',
                'a' => [__('JANMÈ! N ap pa janm mande modpas ou pa mesaj, pa imèl ni nan telefòn. Si yon moun fè sa, se pa nou.')]],
            // ── Frè & Abònman (kat. 7) ──────────────────────────────────────────
            ['id' => 'trial_duration', 'icon' => 'hourglass_top', 'q' => __('Konbyen tan esè gratis la dire?'), 'kw' => 'eseye gratis mwa peryòd di dire',
                'a' => [__('Ou gen 3 mwa eseye platfòm lan san w pa peye anyen.'), __('Pou 3 premye mwa yo, se GRATIS nèt. Apre sa, gen yon ti frè pou kontinye itilize tout fonksyon yo.')]],
            ['id' => 'no_auto_charge', 'icon' => 'credit_card_off', 'q' => __('Èske yo pran lajan nan men mwen otomatikman lè eseye a fini?'), 'kw' => 'otomatikman lajan pran eseye fini abònman',
                'a' => [__('Non! Ou dwe aktive abònman an tèt ou — nou pa pral pran anyen nan men w san akò w.')]],
            // ── Lang & Itilizasyon (kat. 8) ──────────────────────────────────────
            ['id' => 'languages', 'icon' => 'language', 'q' => __('Nan ki lang platfòm la disponib?'), 'kw' => 'lang kreyòl fransè disponib chanje',
                'a' => [__('Kreyòl ak Fransè — de lang ofisyèl pou tout Ayisyen!'), __('Anwo paj la, w ap wè yon bouton ki di "Kreyòl" oswa "Français" — klike sou li pou chanje lang lan nenpòt ki lè.')]],
            ['id' => 'mobile_app', 'icon' => 'phone_android', 'q' => __('Èske gen yon aplikasyon pou telechaje sou magazen an?'), 'kw' => 'aplikasyon telechaje play store app store magazen',
                'a' => [__('Kounye a, se sit entènèt la w ap itilize, men ou ka mete l kòm yon rakoursi sou ekran telefòn ou pou li mache tankou yon aplikasyon.'), __('Li mache byen sou telefòn, sou tablèt ak sou òdinatè tou.')]],
            // ── Sipò (kat. 9) ───────────────────────────────────────────────────
            ['id' => 'contact_support', 'icon' => 'support_agent', 'q' => __('Kijan pou m kontakte sipò a?'), 'kw' => 'sipò kontak asistans whatsapp rele èd telefòn',
                'a' => [__('Ou ka ekri nou sou WhatsApp — lyen an toujou anba chak paj nan sit la.'), __('Nou la pifò lè! Si w ekri epi nou pa reponn imedyatman, n ap reponn w pi vit ke posib.')]],
            ['id' => 'report_bug', 'icon' => 'bug_report', 'q' => __('Kijan pou mwen rapòte yon pwoblèm sou sit la?'), 'kw' => 'rapòte pwoblèm bug tekniko',
                'a' => [__('Ekri nou sou WhatsApp epi dekri pwoblèm nan — si ou kapab voye yon foto, sa ap ede nou pi vit!'), __('Ou ka bay sijesyon tou — n ap kontan tande sa w panse.')]],
            // ── Jeneral (kat. 10) ─────────────────────────────────────────────
            ['id' => 'what_is', 'icon' => 'info', 'q' => __('Kisa LajanAnsanm ye?'), 'kw' => 'lajan ansanm lajanansanm aplikasyon platfòm kisa ye',
                'a' => [__('LajanAnsanm se yon platfòm ki ede w jere sòl ou yo — tradisyon nou an, men pi klè, pi senp, san konplikasyon!'), __('Se yon ekip Ayisyen ki te kreye l pou ede nou jere lajan nou pi byen ansanm.'), __('Nou pa yon bank, nou pa kenbe lajan w — nou jis ede w kenbe kont yo klè ak transparan.')],
                'related' => ['tontine_basics', 'is_it_free']],
            ['id' => 'share_platform', 'icon' => 'share', 'q' => __('Kijan mwen ka envite zanmi m yo itilize LajanAnsanm tou?'), 'kw' => 'envite zanmi pataje sit referans',
                'a' => [__('Pataje lyen sit la avèk yo — di yo se fason ki pi senp pou fè sòl san tèt chaje! Mèsi pou pataje avèk zanmi w yo ❤️')]],
        ];
    }

    private static function admin(): array
    {
        return [
            ['id' => 'validate_sub', 'icon' => 'verified', 'q' => __('Kijan pou m valide yon peman abònman?'), 'kw' => 'abònman valide peman deklarasyon moncash natcash apwouve',
                'a' => [__('Ale nan meni « Abònman », klike sou deklarasyon ki an atant la.'), __('Verifye nimewo tranzaksyon ak montan an sou kont MonCash oswa NatCash ou, answit klike « Valide » pou aktive plan itilizatè a.')],
                'actions' => [[__('Peman an atant'), route('admin.subscriptions')]], 'related' => ['manage_users']],
            ['id' => 'manage_users', 'icon' => 'people', 'q' => __('Kijan pou m jere itilizatè yo?'), 'kw' => 'itilizatè kont moun lis chèche sispann admin',
                'a' => [__('Nan paj « Itilizatè yo », ou ka wè lis tout moun ki kreye kont, kantite sòl yo genyen, epi wè si kont lan aktif oswa sispann.'), __('Ou ka chèche pa non oswa pa imel.')],
                'actions' => [[__('Lis itilizatè yo'), route('admin.users')]], 'related' => ['validate_sub']],
            ['id' => 'system_health', 'icon' => 'dashboard', 'q' => __('Kijan pou m verifye sante platfòm lan?'), 'kw' => 'tablo bo sante estatistik tontin volim administrasyon',
                'a' => [__('Nan « Tablo bò », ou wè rezime jeneral la: kantite sòl aktive, volim lajan k ap vire, abònman ki fenk peye, ak siyal antretyen si genyen.')],
                'actions' => [[__('Tablo bò'), route('admin.overview')]], 'related' => ['help_stats']],
            ['id' => 'help_stats', 'icon' => 'help', 'q' => __('Ki kote pou m wè kesyon itilizatè yo poze?'), 'kw' => 'sant èd estatistik kesyon asistans rapò miss',
                'a' => [__('Nan paj « Sant èd », ou ka wè kesyon itilizatè yo poze souvan ak kesyon ki pat jwenn repons pou nou ka amelyore sistèm nan.')],
                'actions' => [[__('Estatistik èd'), route('admin.help')]], 'related' => ['system_health']],
            ['id' => 'manage_plans', 'icon' => 'workspace_premium', 'q' => __('Kijan plan ak pri yo konfigire?'), 'kw' => 'plan pri limit kout men konbit mèt tontin',
                'a' => [__('Plan yo (Gratis, Kout Men, Konbit, Mèt Sòl) konfigire nan fichye konfigirasyon an ak limit sòl, manm, ak avantaj.'), __('Ou ka gade estati chak abònman nan paj Abònman.')],
                'actions' => [[__('Abònman'), route('admin.subscriptions')]], 'related' => ['validate_sub']],
            ['id' => 'security_logs', 'icon' => 'shield', 'q' => __('Kijan pou m siveye sekirite a?'), 'kw' => 'sekirite journal log aksè siveyans',
                'a' => [__('Aplikasyon an anrejistre tantativ koneksyon, deklarasyon ak aksyon kle yo.'), __('Tout kòd referans ak nimewo tranzaksyon verifye pou evite doublon.')],
                'related' => ['system_health']],
            ['id' => 'notifications', 'icon' => 'notifications', 'q' => __('Kijan notifikasyon yo voye bay itilizatè yo?'), 'kw' => 'notifikasyon imel web push mesaj avètisman',
                'a' => [__('Itilizatè yo resevwa notifikasyon dirèk nan aplikasyon an ak pa imel lè yon peman valide oswa lè yon tou ap pwoche.'), __('Notifikasyon Web Push disponib tou pou moun ki aktive l.')],
                'related' => ['system_health']],
            ['id' => 'maintenance', 'icon' => 'build', 'q' => __('Kijan mòd antretyen fonksyone?'), 'kw' => 'antretyen banyè fèmen sistèm mete ajou',
                'a' => [__('Lè mòd antretyen aktif, yon banyè parèt pou enfòme itilizatè yo.'), __('Administratè a ka toujou gen aksè konplè pou fè ajisteman sou bazdone a.')],
                'related' => ['system_health']],
        ];
    }
}
