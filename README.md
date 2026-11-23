# LajanAnsanm

LajanAnsanm est une application web de gestion de **tontines**. Elle permet aux organisateurs de suivre les membres, les cotisations, les tours et les paiements, tandis que les membres consultent un portail personnel et déclarent leurs paiements.

Le projet est construit avec Laravel 12. L’interface est disponible en créole haïtien et en français.

## Fonctionnalités

- Création et gestion de tontines, de membres, de cycles et de tours.
- Suivi des cotisations et des bénéficiaires, avec historique et rapports.
- Enregistrement de paiements en espèces, MonCash, NatCash ou par virement bancaire.
- Portail membre privé accessible par un lien personnel, sans création de compte membre.
- Déclaration de cotisations, puis validation ou refus par l’organisateur.
- Confirmation ou contestation de la réception d’un versement par un membre.
- Plans d’abonnement, demandes manuelles et paiement en ligne facultatif via MonCashConnect.
- Console propriétaire pour les comptes, plans, abonnements, paramètres et journaux d’activité.

## Architecture

Trois acteurs, trois espaces :

| Rôle | Accès | Authentification |
|------|-------|------------------|
| **Organisateur** | Ses tontines : membres, cycles, cotisations, versements, rapports, déclarations | Compte classique (e-mail vérifié) |
| **Membre** | Son portail personnel : déclarer ses paiements, confirmer ou contester son tour | Lien personnel `/m/{token}` — pas de compte |
| **Propriétaire** | Console `/admin` : comptes, plans, abonnements, paramètres, journaux | Rôle `is_owner` (commande `php artisan lajan:make-owner`) |

Organisation du code :

- `routes/web.php` — routes métier (tontines, portail membre, webhook MonCashConnect)
- `routes/admin.php` — console propriétaire (`EnsureOwner`)
- `routes/subscription.php` — pages d’abonnement et paiement en ligne
- `app/Http/Middleware/EnforceSubscription.php` — limites du plan **côté serveur**
- `app/Support/Entitlements.php` — états d’un compte : `owner | free | active | grace | expired`
- `app/Policies/TontinePolicy.php` — chaque tontine n’appartient qu’à son créateur
- `app/Services/MonCashConnect.php` — passerelle de paiement (webhook vérifié par signature HMAC)

Points de conception :

- `payment_declarations` est séparée de `contributions` : une déclaration non validée n’entre jamais dans les totaux ni les rapports.
- `subscription_payments` est séparée de `subscription_requests` : un paiement en ligne abandonné n’encombre pas la file de validation manuelle.
- Les plans sont figés en snapshot sur chaque abonnement (prix et limites immuables pour l’historique).

## Technologies

- PHP 8.2 ou supérieur
- Laravel 12
- SQLite en développement par défaut ; MySQL pour la production
- Node.js et npm
- Vite, Tailwind CSS et Alpine.js

## Installation locale

Prérequis : PHP, Composer, Node.js et npm.

1. Installer les dépendances PHP :

   ```bash
   composer install
   ```

2. Créer le fichier d’environnement :

   ```powershell
   Copy-Item .env.example .env
   ```

   Sous macOS ou Linux : `cp .env.example .env`.

3. Générer la clé de l’application et préparer la base :

   ```bash
   php artisan key:generate
   php artisan migrate
   ```

   SQLite est utilisé par défaut. Pour MySQL, renseignez les variables `DB_*` dans `.env` **avant** les migrations.

4. Installer et compiler le front-end :

   ```bash
   npm install
   npm run build
   ```

5. Démarrer le serveur :

   ```bash
   php artisan serve
   ```

   L’application est disponible à http://127.0.0.1:8000.

Pour lancer en une fois le serveur Laravel, le worker de file d’attente, les journaux et Vite :

```bash
composer run dev
```

Ne lancez pas `php artisan db:seed` en production : le seeder crée un compte de test.

## Créer un compte propriétaire

Créez d’abord un compte sur `/register`, vérifiez l’e-mail, puis :

```bash
php artisan lajan:make-owner adresse@example.com
```

La console est à `/admin`. Pour retirer le rôle :

```bash
php artisan lajan:make-owner adresse@example.com --revoke
```

Activez ensuite la double vérification dans `/admin/settings/security`. En production, configurez et testez le SMTP **avant** toute inscription.

## Configuration des e-mails

En développement, le mailer `log` écrit les messages dans `storage/logs` au lieu de les envoyer.

Pour un envoi réel, activez la validation en deux étapes sur le compte Google, créez un **mot de passe d’application** (jamais le mot de passe du compte), puis renseignez dans `.env` (jamais dans Git) :

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-adresse@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-dapplication
MAIL_FROM_ADDRESS=votre-adresse@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

`MAIL_SCHEME=smtp` utilise STARTTLS. Après modification : `php artisan config:clear` (ou reconstituez le cache en production), inscrivez un compte de test et vérifiez que le message arrive.

## Connexion Google (optionnel)

Si vous voulez le bouton Google sur `/login` et `/register` :

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI` (ex. `https://www.lajanansanm.com/auth/google/callback`)

Sans `GOOGLE_CLIENT_ID`, le bouton n’apparaît pas.

## Paiement en ligne des abonnements

MonCashConnect est facultatif et désactivé par défaut. Pour l’activer :

- `MONCASHCONNECT_ENABLED=true`
- `MONCASHCONNECT_SECRET_KEY`
- `MONCASHCONNECT_WEBHOOK_SECRET`
- `MONCASHCONNECT_ORIGIN` — domaine public autorisé chez le fournisseur

URL de notification :

```text
https://www.lajanansanm.com/webhooks/moncashconnect
```

Clés sandbox pour les essais, clés live pour les paiements réels. Ne publiez jamais de secret dans le code, le navigateur ou Git.

Les plans payants restent inactifs tant que le propriétaire n’a pas fixé les prix et les coordonnées de paiement dans `/admin`.

## Production

Modèle : [`deploy/.env.production.example`](deploy/.env.production.example). Remplacez toutes les valeurs d’exemple et fournissez les secrets par l’environnement du serveur.

Points essentiels :

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` en HTTPS, `APP_FORCE_HTTPS=true`
- `APP_KEY` générée sur le serveur (`php artisan key:generate --show`), distincte de celle du PC
- MySQL avec un utilisateur dédié (pas `root`)
- document root = dossier `public/`
- `composer install --no-dev --optimize-autoloader` puis `npm ci && npm run build`
- `php artisan migrate --force`
- `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- sauvegarde quotidienne : `deploy/backup.sh` (étendre à `storage/app/private` pour les reçus)
- si TLS se termine sur un reverse proxy, renseigner `TRUSTED_PROXIES` (IPs ou CIDR, pas `*`)

## Tests et vérifications

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

CI GitHub Actions : `.github/workflows/ci.yml` (Pint, tests, build Vite). `AuthorizationMatrixTest` vérifie les routes `tontines.*`.

## Langues

Créole haïtien (`ht`) et français (`fr`). En production, l’exemple utilise `APP_LOCALE=ht` et `APP_FALLBACK_LOCALE=fr`.

## Contact

[lajanansanm.ht@gmail.com](mailto:lajanansanm.ht@gmail.com)
