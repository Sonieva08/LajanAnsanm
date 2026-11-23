# 📘 DOCUMENTATION — LajanAnsam

> **Application web de gestion de tontines**  
> Conçue pour les communautés haïtiennes et francophones

---

## 1. Présentation du projet

**LajanAnsam** (littéralement "argent ensemble" en créole haïtien) est une plateforme web qui aide les organisateurs de tontines à gérer leurs activités de manière simple, transparente et sécurisée.

Une tontine est une forme d'épargne collective où un groupe de personnes cotise régulièrement, et chaque membre reçoit à tour de rôle la somme totale collectée. Gérer cela manuellement (cahiers, messages, calculs de tête) est source d'erreurs et de tensions. LajanAnsam numérise ce processus pour offrir :

- Un suivi clair des cotisations et des versements
- Un historique accessible à tous les membres concernés
- Une réduction des litiges et des oublis
- Un portail personnel pour chaque membre

---

## 2. Qui peut l'utiliser ?

LajanAnsam s'adresse à trois types d'utilisateurs, chacun avec son propre espace :

| Rôle | Qui c'est ? | Ce qu'il peut faire | Comment il se connecte |
|------|-------------|---------------------|------------------------|
| **Organisateur** | La personne qui crée et anime une tontine | Gérer ses tontines, ajouter des membres, créer des cycles et des tours, enregistrer les cotisations, valider les déclarations de paiement, consulter les rapports | Compte classique avec e-mail vérifié |
| **Membre** | Une personne inscrite dans une ou plusieurs tontines | Consulter son portail personnel, déclarer ses paiements, confirmer ou contester la réception d'un versement | Lien personnel `/m/{token}` — **pas de compte à créer** |
| **Propriétaire** | L'administrateur de la plateforme LajanAnsam | Gérer tous les comptes, les plans d'abonnement, les paramètres système et consulter les journaux d'activité | Rôle spécial `is_owner`, attribué via commande CLI |

---

## 3. Fonctionnalités principales

### 👥 Gestion des tontines et des membres
- Créer une tontine avec un nom, un montant de cotisation et une fréquence
- Ajouter et supprimer des membres (avec consentement et protection de la vie privée)
- Suivre qui a payé et qui n'a pas encore payé à chaque tour

### 🔄 Cycles et tours
- Organiser la tontine en cycles (séries de tours)
- À chaque tour, un membre différent est désigné bénéficiaire
- Historique complet des tours passés et à venir

### 💰 Suivi des cotisations et paiements
- Enregistrement des paiements en espèces, MonCash, NatCash ou virement bancaire
- Déclaration de paiement par le membre via son portail
- Validation ou refus de la déclaration par l'organisateur
- Séparation stricte entre les déclarations (en attente) et les cotisations validées : une déclaration non approuvée n'apparaît jamais dans les totaux ni les rapports

### 📊 Rapports et historiques
- Consultation des soldes, des cotisations et des versements par tontine
- Historique des paiements et des réceptions
- Génération de rapports pour chaque tontine

### 💳 Abonnements et paiement en ligne (optionnel)
- Plusieurs plans d'abonnement avec des limites différentes (nombre de tontines, membres, etc.)
- Paiement en ligne des abonnements via **MonCashConnect**
- Système de demandes d'abonnement et de paiements séparés pour éviter les doublons
- Snapshots des plans : les prix et limites sont figés au moment de la souscription

### 🔐 Sécurité et vie privée
- Chaque tontine appartient exclusivement à son créateur (vérifié par des politiques d'autorisation)
- Les membres ne voient pas les informations personnelles (téléphones, paiements) des autres membres
- Case de consentement obligatoire pour ajouter un membre (date enregistrée)
- Politique de confidentialité publique accessible à tous
- Vérification d'e-mail obligatoire pour les organisateurs et propriétaires

## 4. Architecture technique

LajanAnsam est une application **Laravel 12** (PHP 8.2+).

### 🗂️ Organisation du code

| Fichier / Dossier | Rôle |
|-------------------|------|
| `routes/web.php` | Routes métier : tontines, portail membre, webhook MonCashConnect |
| `routes/admin.php` | Console propriétaire (`/admin`) avec middleware `EnsureOwner` |
| `routes/subscription.php` | Pages d'abonnement et paiement en ligne |
| `routes/account.php` | Gestion de compte utilisateur |
| `routes/auth.php` | Authentification (login, register, vérification e-mail) |
| `routes/legal.php` | Pages légales (confidentialité, conditions) |
| `app/Http/Middleware/EnforceSubscription.php` | Applique les limites du plan d'abonnement côté serveur |
| `app/Support/Entitlements.php` | États d'un compte : `owner`, `free`, `active`, `grace`, `expired` |
| `app/Policies/TontinePolicy.php` | Chaque tontine n'appartient qu'à son créateur |
| `app/Services/MonCashConnect.php` | Passerelle de paiement (webhook vérifié par signature HMAC) |
| `app/Models/` | Modèles Eloquent : `User`, `Tontine`, `Member`, `Cycle`, `Contribution`, `Payout`, `PaymentDeclaration`, `Plan`, `Subscription`, etc. |
| `app/Http/Controllers/` | Contrôleurs pour chaque domaine métier |
| `database/migrations/` | Migrations de base de données (versionnées) |
| `lang/` | Traductions en français (`fr`) et créole haïtien (`ht`) |

### 🧩 Points de conception importants

1. **Déclarations séparées des cotisations** (`payment_declarations` vs `contributions`) : une déclaration de paiement doit être validée par l'organisateur avant d'être comptabilisée.
2. **Paiements d'abonnement séparés des demandes** (`subscription_payments` vs `subscription_requests`) : un paiement en ligne abandonné n'encombre pas la file de validation manuelle.

## 5. Technologies utilisées

| Couche | Technologie |
|--------|-------------|
| Backend | PHP 8.2+, Laravel 12 |
| Base de données | SQLite (développement par défaut), MySQL (production) |
| Frontend | Vite, Tailwind CSS, Alpine.js |
| Authentification | Laravel Breeze, Socialite (connexion Google) |
| Paiement | MonCashConnect (optionnel) |
| Tests | PHPUnit (~25 classes de tests fonctionnels) |
| Qualité du code | Laravel Pint, GitHub Actions (CI) |

---

## 6. Installation locale

### Prérequis
- PHP 8.2 ou supérieur
- Composer
- Node.js et npm
- Une base de données (SQLite par défaut, ou MySQL)

### Étapes

```bash
# 1. Cloner le dépôt
git clone https://github.com/Sonieva08/LajanAnsam.git
cd lajanansanm

# 2. Installer les dépendances PHP
composer install

# 3. Créer le fichier d'environnement
# Windows (PowerShell)
Copy-Item .env.example .env

# macOS / Linux
cp .env.example .env

# 4. Générer la clé de l'application et préparer la base de données
php artisan key:generate
php artisan migrate --force

# 5. Installer et compiler les dépendances front-end
npm install
npm run build

# 6. Lancer le serveur de développement
php artisan serve
```

L'application sera accessible à l'adresse : `http://localhost:8000`

### Lancer tout l'environnement de développement d'un coup

```bash
composer run dev
```

Cette commande lance simultanément :
- Le serveur Laravel (`php artisan serve`)
- Le worker de file d'attente (`php artisan queue:listen`)
- Afficheur de logs (`php artisan pail`)
- Vite pour le front-end (`npm run dev`)

## 7. Créer un compte propriétaire (administrateur)

Le premier utilisateur créé via `/register` est un utilisateur normal. Pour lui donner les droits de propriétaire :

```bash
php artisan lajan:make-owner adresse@example.com
```

Pour retirer ce rôle :

```bash
php artisan lajan:make-owner adresse@example.com --revoke
```

Le propriétaire accède ensuite à la console d'administration à l'URL `/admin`.

> ⚠️ Les comptes organisateurs et propriétaires doivent vérifier leur adresse e-mail avant d'accéder aux fonctions protégées. En production, configurez le SMTP avant l'inscription.

---

## 8. Configuration des e-mails

En développement, Laravel utilise le mailer `log` : les e-mails de vérification et de réinitialisation sont écrits dans les journaux, pas envoyés vraiment.

Pour utiliser Gmail en production, configurez ces variables dans votre `.env` :

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=votre-adresse@gmail.com
MAIL_PASSWORD=votre-mot-de-passe-dapplication
MAIL_FROM_ADDRESS=votre-adresse@gmail.com
MAIL_FROM_NAME="LajanAnsam"
```

> ⚠️ N'utilisez pas votre mot de passe Gmail habituel. Activez la validation en deux étapes et créez un **mot de passe d'application**. Ne commitez jamais ce fichier `.env` dans Git.

---

## 9. Activer les paiements en ligne (MonCashConnect)

L'intégration MonCashConnect est **optionnelle et désactivée par défaut**.

Pour l'activer, renseignez ces variables d'environnement :

```dotenv
MONCASHCONNECT_ENABLED=true
MONCASHCONNECT_SECRET_KEY=votre_cle_secrete
MONCASHCONNECT_WEBHOOK_SECRET=votre_webhook_secret
MONCASHCONNECT_ORIGIN=https://www.lajanansanm.com
```

Configurez ensuite l'URL de notification dans le tableau de bord MonCashConnect :

```
https://www.lajanansanm.com/webhooks/moncashconnect
```

Utilisez les clés **sandbox** pour les tests et les clés **live** pour les paiements réels.

---

## 10. Langues supportées

LajanAnsam est disponible en :
- **Français** (`fr`)
- **Créole haïtien** (`ht`)

La langue peut être changée depuis l'application. Les langues par défaut et de secours sont configurées via `APP_LOCALE` et `APP_FALLBACK_LOCALE` dans le fichier `.env`.

---

## 11. Tests et qualité du code

### Lancer les tests

```bash
php artisan test
```

Le projet contient environ 25 classes de tests fonctionnels, dont `AuthorizationMatrixTest` qui vérifie automatiquement toutes les routes `tontines.*`.

### Vérifier le style du code

```bash
# Formatter le code avec Pint
./vendor/bin/pint

# Vérifier sans modifier
./vendor/bin/pint --test
```

### Compiler les assets front-end

```bash
npm run build
```

---

## 12. Déploiement en production

Consultez le fichier [`deploy/.env.production.example`](deploy/.env.production.example) pour la configuration complète (MySQL, HTTPS, SMTP, MonCashConnect).

Ce fichier est un modèle : remplacez toutes les valeurs d'exemple et fournissez les secrets via l'environnement du serveur.

---

## 13. Contact et support

Pour toute question concernant le projet, écrivez à :

📧 **lajanansanm.ht@gmail.com**

---

## 14. Licence

Ce projet est sous licence **MIT**. Voir le fichier `LICENSE` pour plus de détails.

---

> 💡 **Besoin d'aide pour commencer ?**  
> 1. Installez le projet localement (voir section 6)  
> 2. Créez votre premier compte  
> 3. Créez une tontine et invitez vos membres  
> 4. Suivez vos cotisations et vos tours en toute transparence
