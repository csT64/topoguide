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

| Dépendance | Version minimale |
|---|---|
| PHP | 7.4 |
| Yii2 | ~2.0.30 |
| yii2-bootstrap5 | ~2.0 |
| yii2-queue | ~2.3 |
| yii2-httpclient | ~2.0 |
| MySQL / MariaDB | 5.7+ |

---

## 2. Installation via Composer

Le dépôt source est `https://github.com/csT64/tourinsoft-client`.

Si le package n'est pas publié sur Packagist, l'ajouter en dépôt VCS dans `composer.json` de l'application :

```json
"repositories": [
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
composer update adt64/tourinsoft-client
```

L'autoload PSR-4 `tourinsoftclient\\` → `src/` est déclaré dans le `composer.json` du package et
pris en charge automatiquement par Composer.

---

## 3. Créer la table queue

Le module utilise `yii\queue\db\Queue`. Si la table `queue` n'existe pas encore :

```bash
php yii migrate --migrationPath=@vendor/yiisoft/yii2-queue/src/drivers/db/migrations
```

---

## 4. Exécuter les migrations du module

```bash
php yii migrate --migrationPath=@vendor/adt64/tourinsoft-client/src/migrations
```

Tables créées :

| Table | Contenu |
|---|---|
| `tc_sources` | Sources Tourinsoft configurées |
| `tc_extraction_configs` | Configurations d'extraction (mapping champs) |
| `tc_jobs` | Historique des jobs d'import |
| `tc_notifications` | Notifications envoyées |

Les migrations suivantes sont également présentes pour les évolutions de schéma :
`m251201_*`, `m260126_*`, `m260418_*` — elles s'appliquent dans le même ordre.

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
$config['modules']['tourinsoft-client'] = [
    'class' => 'tourinsoftclient\Module',
    'extractorApiUrl'   => $localParams['extractorApiUrl']   ?? 'http://extracteur-tourinsoft.local/api/v1',
    'extractorApiToken' => $localParams['extractorApiToken'] ?? 'change-me',
];
```

### 5c. Ajouter le bootstrap et la queue

Dans la clé `bootstrap` :

```php
'bootstrap' => ['log', 'tourinsoftclient\components\TourinsoftClientBootstrap'],
```

Dans la clé `components` :

```php
'queue' => [
    'class' => 'yii\queue\db\Queue',
    'db' => 'db',
    'tableName' => '{{%queue}}',
    'channel' => 'default',
    'mutex' => 'yii\mutex\MysqlMutex',
],
```

### 5d. Log dédié aux extractions (recommandé)

```php
'log' => [
    'targets' => [
        // ... vos targets existants ...
        [
            'class' => 'yii\log\FileTarget',
            'levels' => ['error', 'warning', 'info', 'trace'],
            'categories' => ['extraction.*'],
            'logFile' => '@runtime/logs/extraction.log',
            'maxFileSize' => 1024 * 2,
            'maxLogFiles' => 5,
        ],
    ],
],
```

---

## 6. Configurer `config/console.php`

Même pattern que `web.php` pour la console (worker queue, commandes cron) :

```php
$localParams = file_exists(__DIR__ . '/params-local.php')
    ? require __DIR__ . '/params-local.php'
    : [];

// ...

$config['modules']['tourinsoft-client'] = [
    'class' => 'tourinsoftclient\Module',
    'extractorApiUrl'   => $localParams['extractorApiUrl']   ?? 'http://extracteur-tourinsoft.local/api/v1',
    'extractorApiToken' => $localParams['extractorApiToken'] ?? 'change-me',
];
```

---

## 7. Créer `config/params-local.php` (non commité)

Ce fichier est gitignorié (`/config/*.local.php`). À créer sur chaque serveur :

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

## 8. Démarrer le worker queue

Les jobs d'extraction sont exécutés en arrière-plan par un worker Yii2 :

```bash
# Démarrage manuel
php yii queue/run

# Démarrage en daemon (recommandé en production)
php yii queue/listen --sleep=3
```

En production, superviser avec `systemd` ou `supervisor`.

---

## 9. Vérifier l'installation

```bash
# Santé de l'API extracteur
curl -H "Authorization: Bearer <token>" https://extracteur-tourinsoft.aadt64.fr/api/v1/health

# Interface web
https://votre-app/index.php?r=tourinsoft-client/sources/index
```

Sur la page Sources, le module affiche l'URL API active. Si elle est incorrecte, vérifier
`params-local.php` et relancer PHP-FPM (`sudo systemctl reload php8.x-fpm`).

---

## 10. Structure du module (référence rapide)

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
├── migrations/                       # 8 migrations cumulatives
└── views/                            # Vues Yii2 (sources, config, jobs, dashboard)
```

---

## 11. Points d'attention

- **Opcache** : après un `git pull`, recharger PHP-FPM pour vider le cache bytecode.
- **Worker console vs web** : ce sont deux processus PHP distincts. Un restart du worker
  est nécessaire après modification de fichiers utilisés par les jobs.
- **Token 401** : le token Bearer du client (`extractorApiToken`) doit être identique à la
  valeur attendue par l'extracteur. Vérifier les deux configurations.
- **Chemins JSON imbriqués** : le mapping supporte la notation `adresses.[].code_postal`
  (tableau), `moyencoms.[typedacces_telecom.thes_code=C1].coordonnees_telecom` (filtre
  conditionnel) et `etapess.[].nom_etape` avec destination multi-tables.
  Voir `docs/GUIDE_MAPPING_API_V3_COMPLET.md` pour le détail.
