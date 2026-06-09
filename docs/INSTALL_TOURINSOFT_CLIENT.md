# Installation du composant `tourinsoft-client` sur une application Yii2

## Contexte

`tourinsoft-client` est un module Yii2 (type `yii2-extension`) qui permet à une application de
consommer les données extraites par `extracteur-tourinsoft`. Il fournit :

- une interface d'administration (sources, configs, jobs, dashboard)
- un service d'import (`DataImportService`) qui résout les chemins JSON et insère en base
- un orchestrateur (`ExtractionOrchestrator`) qui pilote le cycle complet via une queue Yii2
- un service API (`TourinsoftApiService`) qui appelle l'extracteur via Bearer token

---

## 1. Prérequis

| Dépendance | Version minimale | Notes |
|---|---|---|
| **PHP** | **8.4** | `symfony/mailer` v8 (dépendance transitive) impose PHP ≥ 8.4 |
| Yii2 | ~2.0.30 | |
| yii2-bootstrap5 | ~2.0 | |
| yii2-queue | ~2.3 | driver DB ; table `queue` à créer |
| yii2-httpclient | ~2.0 | |
| MySQL / MariaDB | 5.7+ | |

> **Important** : PHP 8.2 ou 8.3 ne fonctionnent pas — `symfony/mailer ^8` est une dépendance
> transitive de `yii2-symfonymailer` et exige PHP 8.4 strict.

---

## 2. Installation via Composer

### 2a. Développement local (dépôt source présent en chemin relatif)

Lorsque le code source de `tourinsoft-client` se trouve dans un dossier frère (ex. `../tourinsoft-client`) :

```json
"repositories": [
    { "type": "composer", "url": "https://asset-packagist.org" },
    {
        "type": "path",
        "url": "../tourinsoft-client",
        "options": { "symlink": true }
    }
],
"require": {
    "adt64/tourinsoft-client": "@dev"
}
```

Composer crée un lien symbolique `vendor/adt64/tourinsoft-client → ../tourinsoft-client`.
Toute modification dans le source est immédiatement reflétée sans `composer update`.

### 2b. Déploiement serveur (dépôt Git distant)

```json
"repositories": [
    { "type": "composer", "url": "https://asset-packagist.org" },
    {
        "type": "vcs",
        "url": "https://github.com/csT64/tourinsoft-client"
    }
],
"require": {
    "adt64/tourinsoft-client": "dev-claude/connect-git-branch-i6k3t"
}
```

Puis :

```bash
php8.4 /usr/local/bin/composer update adt64/tourinsoft-client
```

L'autoload PSR-4 `tourinsoftclient\\` → `src/` est déclaré dans le `composer.json` du package.

---

## 3. Créer la table `queue`

Le module utilise `yii\queue\db\Queue`. La table `queue` doit exister avant tout lancement de job.

Les classes de migration de `yii2-queue` utilisent des **namespaces**, ce qui impose une syntaxe
différente de la commande habituelle :

```bash
php8.4 yii migrate \
  --migrationNamespaces="yii\\queue\\db\\migrations" \
  --migrationPath="" \
  --interactive=0
```

> `--migrationPath=""` est obligatoire pour désactiver le chemin par défaut `@app/migrations`
> et éviter une erreur de mélange de styles.

---

## 4. Exécuter les migrations du module

### Installation fraîche — une seule commande

```bash
php8.4 yii migrate \
  --migrationPath=@vendor/adt64/tourinsoft-client/src/migrations/install \
  --interactive=0
```

Cette migration consolidée crée les 4 tables en une passe, avec toutes les colonnes
finales déjà incluses (soft delete, versioning, logs LONGTEXT). Pas de données de démo.

Tables créées :

| Table | Contenu |
|---|---|
| `tc_sources` | Sources Tourinsoft configurées |
| `tc_extraction_configs` | Configurations d'extraction (mapping champs) |
| `tc_jobs` | Historique des jobs d'import |
| `tc_notifications` | Notifications envoyées |

### Mise à jour d'une installation existante

Appliquer uniquement les migrations incrémentales (ne pas réexécuter le chemin `install/`) :

```bash
php8.4 yii migrate \
  --migrationPath=@vendor/adt64/tourinsoft-client/src/migrations \
  --interactive=0
```

---

## 5. Configurer `config/web.php`

### 5a. Charger les surcharges locales (multi-environnement)

En haut de `web.php`, après les premiers `require` :

```php
$localParams = file_exists(__DIR__ . '/params-local.php')
    ? require __DIR__ . '/params-local.php'
    : [];
```

### 5b. Déclarer le module

```php
'modules' => [
    'tourinsoft-client' => [
        'class' => 'tourinsoftclient\Module',
        'extractorApiUrl'   => $localParams['extractorApiUrl']   ?? 'http://extracteur-tourinsoft.local/api/v1',
        'extractorApiToken' => $localParams['extractorApiToken'] ?? 'change-me',
    ],
    // ... autres modules ...
],
```

### 5c. Bootstrap et queue

Dans la clé `bootstrap` :

```php
'bootstrap' => ['log', 'tourinsoftclient\components\TourinsoftClientBootstrap'],
```

> Le bootstrap injecte automatiquement toutes les routes du module via `addRules()`.
> Il n'est **pas** nécessaire d'ajouter des règles dans `urlManager`.

Dans la clé `components` :

```php
'queue' => [
    'class'     => 'yii\queue\db\Queue',
    'db'        => 'db',
    'tableName' => '{{%queue}}',
    'channel'   => 'default',
    'mutex'     => 'yii\mutex\MysqlMutex',
],
```

### 5d. Log dédié aux extractions (recommandé)

```php
'log' => [
    'targets' => [
        // ... vos targets existants ...
        [
            'class'       => 'yii\log\FileTarget',
            'levels'      => ['error', 'warning', 'info'],
            'categories'  => ['extraction.*'],
            'logFile'     => '@runtime/logs/extraction.log',
            'maxFileSize' => 2048,
            'maxLogFiles' => 5,
        ],
    ],
],
```

---

## 6. Configurer `config/console.php`

Même pattern que `web.php`. Sans cette config, `yii queue/listen` ne trouve pas la queue
et le module est absent des commandes console :

```php
$localParams = file_exists(__DIR__ . '/params-local.php')
    ? require __DIR__ . '/params-local.php'
    : [];

$config = [
    // ...
    'bootstrap' => ['log', 'tourinsoftclient\components\TourinsoftClientBootstrap'],
    'components' => [
        'queue' => [
            'class'     => 'yii\queue\db\Queue',
            'db'        => 'db',
            'tableName' => '{{%queue}}',
            'channel'   => 'default',
            'mutex'     => 'yii\mutex\MysqlMutex',
        ],
        // ...
    ],
    'modules' => [
        'tourinsoft-client' => [
            'class'             => 'tourinsoftclient\Module',
            'extractorApiUrl'   => $localParams['extractorApiUrl']   ?? 'http://extracteur-tourinsoft.local/api/v1',
            'extractorApiToken' => $localParams['extractorApiToken'] ?? 'change-me',
        ],
    ],
    // ...
];
```

---

## 7. Créer `config/params-local.php` (non commité)

Ce fichier est gitignorié. À créer sur **chaque serveur** (dev et prod) :

```php
<?php
return [
    'extractorApiUrl'   => 'https://extracteur-tourinsoft.aadt64.fr/api/v1',
    'extractorApiToken' => 'le-vrai-token-partage-avec-extracteur',
];
```

> Le token doit correspondre exactement à celui configuré côté `extracteur-tourinsoft`.
> Un token incorrect provoque une erreur **401 Unauthorized**.

---

## 8. Vérifier `web/index.php` en production

S'assurer que `YII_ENV` n'est **pas** forcé à `'dev'` sur le serveur de production, sous peine
d'une erreur fatale (`yii\debug\Module` non installé) :

```php
// Correct pour la production :
defined('YII_ENV') or define('YII_ENV', 'prod');

// À éviter en production :
// defined('YII_ENV') or define('YII_ENV', 'dev');  // <-- provoque une erreur 500
```

---

## 9. Démarrer le worker queue

Les jobs d'extraction sont exécutés en arrière-plan par un worker Yii2 :

```bash
# Test ponctuel (traite les jobs en attente puis s'arrête)
php8.4 yii queue/run

# Mode daemon (tourne en continu, recommandé en production)
php8.4 yii queue/listen --sleep=3
```

### Service systemd (production)

Créer `/etc/systemd/system/topoguide-queue.service` :

```ini
[Unit]
Description=Topoguide Yii2 Queue Worker
After=network.target mariadb.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/srv/topoguide
ExecStart=/usr/bin/php8.4 /srv/topoguide/yii queue/listen --sleep=3
Restart=on-failure
RestartSec=5s
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

Puis :

```bash
systemctl daemon-reload
systemctl enable --now topoguide-queue.service
systemctl status topoguide-queue.service
```

---

## 10. Vérifier l'installation

```bash
# Santé de l'API extracteur
curl -H "Authorization: Bearer <token>" https://extracteur-tourinsoft.aadt64.fr/api/v1/health

# État du worker queue
systemctl status topoguide-queue.service

# Tables présentes en base
mysql -u topoguide -p topoguide -e "SHOW TABLES LIKE 'tc_%'; SHOW TABLES LIKE 'queue';"
```

Interface web :

```
https://votre-app/tourinsoft-client/sources/index    → liste des sources
https://votre-app/tourinsoft-client/config/index     → configurations d'extraction
https://votre-app/tourinsoft-client/dashboard/index  → tableau de bord
```

Sur la page Sources, le module affiche l'URL API active. Si elle est incorrecte, vérifier
`params-local.php` et relancer PHP-FPM (`sudo systemctl reload php8.4-fpm`).

---

## 11. Structure du module (référence rapide)

```
src/
├── Module.php                        # Point d'entrée, propriétés extractorApiUrl/Token
├── components/
│   └── TourinsoftClientBootstrap.php # Bootstrap Yii2 (routes, components, aliases)
├── controllers/                      # Web + API + Console controllers
├── models/
│   ├── TcSource.php                  # Source Tourinsoft
│   ├── TcExtractionConfig.php        # Config d'extraction (mapping JSON→BDD)
│   ├── TcJob.php                     # Job d'import
│   └── forms/
│       ├── ExtractionConfigForm.php  # Formulaire de mapping multi-tables
│       └── SettingsForm.php          # Formulaire paramètres module
├── services/
│   ├── TourinsoftApiService.php      # Client HTTP vers l'extracteur
│   ├── ExtractionOrchestrator.php    # Pilote le cycle extraction → import
│   └── DataImportService.php         # Résolution chemins JSON, INSERT/UPDATE BDD
├── migrations/
│   ├── install/                      # Migration consolidée (installation fraîche)
│   └── m*.php                        # Migrations incrémentales (mises à jour)
└── views/                            # Vues Yii2 (sources, config, jobs, dashboard)
```

---

## 12. Points d'attention

| Problème | Cause | Solution |
|---|---|---|
| `PHP >=8.4.0 is required` au composer update | `symfony/mailer` v8 via `yii2-symfonymailer` | Installer PHP 8.4 (`a2dismod php8.x && a2enmod php8.4`) |
| `Failed to instantiate component 'yii\debug\Module'` | `YII_ENV='dev'` en production | Corriger `web/index.php` → `define('YII_ENV', 'prod')` |
| `Unknown command: queue/listen` | `queue` absent de `console.php` | Ajouter composant queue + bootstrap dans `console.php` |
| Tables `tc_*` absentes | `vendor/adt64` n'existe pas (package non installé) | Vérifier `composer.json` (repo + require) puis `composer update` |
| Migration queue : `NotInstantiableException` | `--migrationPath` utilisé à la place de `--migrationNamespaces` | Utiliser `--migrationNamespaces="yii\\queue\\db\\migrations" --migrationPath=""` |
| Erreur 401 sur l'API | Token incorrect ou `params-local.php` absent/mal configuré | Vérifier le token des deux côtés (client et extracteur) |
| URLs du module avec `?r=...` dans les menus | Routes du bootstrap non actives (version ancienne du bootstrap) | Mettre à jour `TourinsoftClientBootstrap.php` pour utiliser `addRules()` |
| **Opcache** | Après `git pull`, le bytecode mis en cache peut pointer vers l'ancien code | `systemctl reload php8.4-fpm` après tout déploiement |
