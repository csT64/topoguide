# Synthèse technique — Projet Topoguide

> Document de référence pour le nouveau projet `topoguide` (migration depuis `moteur-v14`).
> Voir [`plan_migration_yii2.md`](./plan_migration_yii2.md) pour le plan de migration phase par phase.

---

## 1. Présentation du projet

Le projet **Topoguide** génère à la demande des fiches PDF multilingues (FR / EN / ES) pour les itinéraires de randonnée du département des Pyrénées-Atlantiques (Tourisme 64), à partir des données saisies dans l'application TourInSoft.

Il assure aussi :
- Le rendu de cartes Leaflet utilisées comme source de captures JPG (CutyCapt)
- Un batch de génération des captures carte
- Une interface d'administration (CRUD, gestion des captures, logs)

Ce qu'il **ne fait pas** : la syndication TourInSoft (application externe distincte qui alimente la BDD).

---

## 2. Stack technique

| Couche | Technologie | Version |
|---|---|---|
| Langage | PHP | 8.2 (Debian 12 natif) |
| Framework | Yii2 Basic | ~2.0.51 |
| ORM | Yii2 ActiveRecord | (inclus Yii2) |
| PDF | TCPDF | ~6.7.0 |
| Cartes | Leaflet | 1.3.1 (vendored) |
| Capture carte | CutyCapt + Xvfb | Debian APT |
| Front admin | jQuery 3 + Bootstrap 3 | Bower |
| Base de données | MariaDB | 10.5+ |
| Serveur web | Apache 2.4 | mod_rewrite + mod_ssl |
| OS serveur | Debian | 12 (Bookworm) |
| Déploiement | Manuel | git pull + composer install |

---

## 3. Architecture applicative

```
/srv/topoguide/
├── commands/
│   └── ScreenshotController.php       ← batch : capture carte → JPG
│
├── components/
│   ├── pdf/
│   │   ├── MYPDF.php                  ← extension TCPDF (header/footer)
│   │   ├── TopoguideHelpers.php       ← clean(), cleanGps(), titre2(), titre3()
│   │   └── TopoguideService.php       ← logique de construction du PDF
│   └── map/
│       └── ScreenshotService.php      ← appel CutyCapt, gestion cache JPG
│
├── config/
│   ├── web.php                        ← config web + URL rules
│   ├── console.php                    ← config console (batch)
│   ├── params.php                     ← constantes (paths, urls, jeton)
│   └── db.php                         ← connexion BDD (non versionné)
│
├── controllers/
│   ├── TopoguideController.php        ← action pdf($lang, $id) — public
│   └── GmapController.php             ← actions simple(), gpx(), kml() — public
│
├── models/
│   ├── Itineraire.php                 ← tables fr / en / es
│   ├── ItineraireSearch.php
│   ├── Producteur.php
│   ├── ProducteurSearch.php
│   ├── Ville.php
│   ├── VilleSearch.php
│   └── User.php                       ← authentification admin
│
├── modules/
│   └── admin/                         ← backoffice (accès authentifié)
│       ├── Module.php
│       ├── controllers/
│       │   ├── DefaultController.php      ← tableau de bord
│       │   ├── ItineraireController.php   ← CRUD fr/en/es
│       │   ├── ProducteurController.php   ← CRUD + upload logo
│       │   ├── VilleController.php        ← CRUD zoom
│       │   ├── CarteController.php        ← gestion captures JPG
│       │   └── LogController.php          ← visualisation erreurs
│       └── views/
│           └── ...
│
├── views/
│   └── gmap/
│       ├── simple.php
│       ├── gpx.php
│       └── kml.php
│
├── web/                               ← document root Apache
│   ├── index.php
│   ├── .htaccess
│   └── gmap/                          ← assets Leaflet, plugins GPX/KML
│
├── runtime/                           ← logs, cache Yii2 (non versionné)
├── vendor/                            ← dépendances Composer (non versionné)
├── composer.json
├── yii                                ← point d'entrée CLI (batch)
└── db.php.dist                        ← template config BDD
```

---

## 4. Base de données

**Base unique : `topoguide`** sur tous les environnements.

### Tables principales

| Table | Colonnes clés | Rôle | Alimentée par |
|---|---|---|---|
| `fr` | 59 champs (id, titre_2, GPS, JSON, photos, étapes, etc.) | Itinéraires français | Syndication TourInSoft |
| `en` | Identique | Itinéraires anglais | Syndication TourInSoft |
| `es` | Identique | Itinéraires espagnols | Syndication TourInSoft |
| `producteur` | id, raison_sociale, adresse, tel, url, logo | Producteurs / offices | Syndication + admin |
| `ville` | ville_id, ville_code, default_zoom | Niveaux de zoom carte | Admin |
| `users` | id, username, password, email, auth_key, status | Comptes admin | Admin |

### Table `users`

```sql
CREATE TABLE users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    username     VARCHAR(64)  NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    email        VARCHAR(128) NOT NULL UNIQUE,
    auth_key     VARCHAR(32)  NOT NULL,
    access_token VARCHAR(64)  DEFAULT NULL,
    status       TINYINT      NOT NULL DEFAULT 10,  -- 10 actif, 0 désactivé
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Champs JSON des itinéraires

Plusieurs champs des tables `fr`/`en`/`es` contiennent des données sérialisées JSON :

| Champ | Structure |
|---|---|
| `photo` | Tableau d'objets `{url, legende}` |
| `etapes` | Tableau d'objets `{nom, lat, lon}` (format V3) |
| `point_d_interet` | Tableau d'objets `{nom, lat, lon, descriptif}` |
| `equipement` | Tableau de chaînes |
| `point_d_attention` | Tableau de chaînes |
| `difficulte` | Objet `{val, label}` |
| `type` | Objet `{val, label}` |
| `locomotion` | Objet `{val, label}` |
| `duree` | Objet `{val, label}` |

> **Attention** : les anciens exports peuvent contenir l'encodage sérialisé `#`/`|` dans le champ `etapes` — le `ScreenshotService` gère les deux formats.

---

## 5. Environnements de déploiement

### 5.1 — Local (développement)

| Paramètre | Valeur |
|---|---|
| Domaine | `api.local` |
| Protocol | HTTP |
| URL PDF | `http://api.local/topoguide/fr/{id}.pdf` |
| URL admin | `http://api.local/admin` |
| Répertoire app | `/srv/topoguide` |
| Document root | `/srv/topoguide/web` |
| Cache cartes | `/cache/capture-gmap` |
| BDD host | `localhost` |
| BDD nom | `topoguide` |
| Log | `/srv/topoguide/runtime/logs/topoguide.log` |
| CutyCapt / Xvfb | À installer (voir §6.3) |
| SSL | Non |

### 5.2 — Recette

| Paramètre | Valeur |
|---|---|
| Domaine | `api.adt64.fr` |
| Protocol | HTTPS |
| URL PDF | `https://api.adt64.fr/topoguide/fr/{id}.pdf` |
| URL admin | `https://api.adt64.fr/admin` |
| Répertoire app | `/srv/topoguide` |
| Document root | `/srv/topoguide/web` |
| Cache cartes | `/cache/capture-gmap` |
| BDD host | `localhost` |
| BDD nom | `topoguide` |
| Log | `/srv/topoguide/runtime/logs/topoguide.log` |
| CutyCapt / Xvfb | À vérifier |
| SSL | Oui (certificat Let's Encrypt ou équivalent) |

### 5.3 — Production

| Paramètre | Valeur |
|---|---|
| Domaine | `api.tourisme64.com` |
| Protocol | HTTPS |
| URL PDF | `https://api.tourisme64.com/topoguide/fr/{id}.pdf` |
| URL admin | `https://api.tourisme64.com/admin` |
| Répertoire app | `/srv/topoguide` |
| Document root | `/srv/topoguide/web` |
| Cache cartes | `/cache/capture-gmap` |
| BDD host | `localhost` |
| BDD nom | `topoguide` |
| Log | `/srv/topoguide/runtime/logs/topoguide.log` |
| CutyCapt / Xvfb | À vérifier |
| SSL | Oui |

---

## 6. Installation et déploiement

### 6.1 — Prérequis système (Debian 12)

```bash
# PHP 8.2 + extensions requises par Yii2
apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql \
               php8.2-mbstring php8.2-xml php8.2-curl php8.2-gd \
               php8.2-intl php8.2-zip

# Apache
apt install -y apache2
a2enmod rewrite ssl headers

# MariaDB
apt install -y mariadb-server

# Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# CutyCapt + Xvfb (capture carte)
apt install -y xvfb cutycapt
```

### 6.2 — Déploiement de l'application

```bash
# Cloner le dépôt
git clone https://github.com/csT64/topoguide.git /srv/topoguide

# Dépendances PHP
cd /srv/topoguide
composer install --no-dev --optimize-autoloader

# Créer le fichier de config BDD (ne jamais versionner db.php)
cp db.php.dist config/db.php
# → éditer config/db.php avec les credentials de l'environnement

# Permissions
chown -R www-data:www-data /srv/topoguide/runtime /srv/topoguide/web/assets
chmod -R 775 /srv/topoguide/runtime /srv/topoguide/web/assets

# Créer le dossier cache cartes
mkdir -p /cache/capture-gmap
chown www-data:www-data /cache/capture-gmap

# Migrations Yii2 (création tables users, etc.)
php /srv/topoguide/yii migrate
```

### 6.3 — Installation de CutyCapt (local)

```bash
apt install -y xvfb cutycapt

# Test
xvfb-run --server-args="-screen 0, 1240x877x24" \
  cutycapt --url="http://api.local/gmap/simple?lat=43.29&lon=-0.37&zoom=13" \
           --out=/tmp/test.jpg --delay=1000
```

### 6.4 — Mise à jour (déploiement d'une nouvelle version)

```bash
cd /srv/topoguide
git pull origin main
composer install --no-dev --optimize-autoloader
php yii migrate         # si nouvelles migrations
php yii cache/flush-all # vider le cache Yii2
```

---

## 7. Configuration Apache2

### VHost local (`/etc/apache2/sites-available/topoguide-local.conf`)

```apache
<VirtualHost *:80>
    ServerName api.local
    DocumentRoot /srv/topoguide/web

    <Directory /srv/topoguide/web>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/topoguide-local-error.log
    CustomLog ${APACHE_LOG_DIR}/topoguide-local-access.log combined
</VirtualHost>
```

### VHost recette (`/etc/apache2/sites-available/topoguide-recette.conf`)

```apache
<VirtualHost *:80>
    ServerName api.adt64.fr
    Redirect permanent / https://api.adt64.fr/
</VirtualHost>

<VirtualHost *:443>
    ServerName api.adt64.fr
    DocumentRoot /srv/topoguide/web

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/api.adt64.fr.crt
    SSLCertificateKeyFile /etc/ssl/private/api.adt64.fr.key

    <Directory /srv/topoguide/web>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/topoguide-recette-error.log
    CustomLog ${APACHE_LOG_DIR}/topoguide-recette-access.log combined
</VirtualHost>
```

### VHost production (`/etc/apache2/sites-available/topoguide-prod.conf`)

```apache
<VirtualHost *:80>
    ServerName api.tourisme64.com
    Redirect permanent / https://api.tourisme64.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName api.tourisme64.com
    DocumentRoot /srv/topoguide/web

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/api.tourisme64.com.crt
    SSLCertificateKeyFile /etc/ssl/private/api.tourisme64.com.key

    <Directory /srv/topoguide/web>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Sécurité headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"

    ErrorLog  ${APACHE_LOG_DIR}/topoguide-prod-error.log
    CustomLog ${APACHE_LOG_DIR}/topoguide-prod-access.log combined
</VirtualHost>
```

### `.htaccess` (web/)

```apache
Options -Indexes

RewriteEngine On

# Rediriger vers index.php si fichier/dossier inexistant
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php [L]
```

---

## 8. Configuration de l'application

### `config/db.php` (non versionné)

```php
<?php
return [
    'class'       => 'yii\db\Connection',
    'dsn'         => 'mysql:host=localhost;dbname=topoguide;charset=utf8mb4',
    'username'    => 'topoguide',
    'password'    => 'REPLACE_ME',
    'tablePrefix' => '',
    'charset'     => 'utf8mb4',
];
```

### `config/params.php`

```php
<?php
return [
    // Chemins système
    'pathCacheGmap'  => '/cache/capture-gmap',
    'pathFontsTcpdf' => '/srv/topoguide/vendor/tecnickcom/tcpdf/fonts',
    'logFile'        => '/srv/topoguide/runtime/logs/topoguide.log',

    // URL de base pour CutyCapt (doit pointer vers le VHost local de l'app)
    'baseUrlGmap'    => 'http://api.local',   // à surcharger en prod

    // CDN médias TourInSoft
    'mediaCdnUrl'    => 'https://cdt64.media.tourinsoft.eu/upload',

    // Jeton de sécurité pour exec.php (déclenchement batch HTTP)
    'execJeton'      => 'REPLACE_ME',
];
```

### `config/web.php` — Règles d'URL

```php
'urlManager' => [
    'enablePrettyUrl' => true,
    'showScriptName'  => false,
    'rules' => [
        // PDF public (lang 2 lettres, id commence par ITIAN ou ITIAQU)
        'topoguide/<lang:[a-z]{2}>/<id:[A-Z0-9]+>.pdf' => 'topoguide/pdf',

        // Cartes Leaflet (appelées par CutyCapt)
        'gmap/simple' => 'gmap/simple',
        'gmap/gpx'    => 'gmap/gpx',
        'gmap/kml'    => 'gmap/kml',

        // Admin
        'admin'                               => 'admin/default/index',
        'admin/<controller:[a-z-]+>'          => 'admin/<controller>/index',
        'admin/<controller:[a-z-]+>/<action>' => 'admin/<controller>/<action>',
    ],
],
```

---

## 9. Cron

> **À définir.** Le batch de génération des captures carte sera planifié ultérieurement.

Template cron envisagé :

```cron
# Génération des captures manquantes ou modifiées depuis 24h — toutes les nuits à 2h
0 2 * * * www-data /usr/bin/php /srv/topoguide/yii screenshot/run >> /srv/topoguide/runtime/logs/screenshot.log 2>&1
```

---

## 10. Dépôt Git et workflow

| Élément | Valeur |
|---|---|
| Dépôt | `https://github.com/csT64/topoguide` (à créer) |
| Branche principale | `main` |
| Branche de dev | `develop` ou feature branches |
| Fichiers non versionnés | `config/db.php`, `vendor/`, `runtime/`, `web/assets/` |

### `.gitignore` minimal

```
/vendor/
/runtime/logs/
/runtime/cache/
/web/assets/
config/db.php
*.log
```

---

## 11. Points d'attention

### Format `etapes`

Le champ `etapes` des tables `fr`/`en`/`es` existe en deux formats selon l'âge des données :
- **Format V3 (actuel)** : JSON `[{nom, lat, lon}, …]`
- **Ancien format** : chaîne sérialisée avec séparateurs `#` et `|`

Le `ScreenshotService` doit gérer les deux formats pendant la période de transition.

### TCPDF et PHP 8

TCPDF 6.7+ est requis pour PHP 8.2. Ne pas utiliser TCPDF < 6.4 avec PHP 8.

### Upload logo producteur

Prévoir le répertoire `web/producteur/logos/` pour les logos uploadés via l'admin, avec :
- Droits d'écriture `www-data`
- Taille maximale PHP `upload_max_filesize = 5M`
- Types acceptés : JPG, PNG, SVG

### Génération batch depuis l'admin

Le `CarteController::actionGenererManquantes()` lance le batch via `exec(...&)` (fire-and-forget). Pour un suivi temps réel, envisager **Yii2 Queue** à terme.

### CutyCapt en production

CutyCapt nécessite un display virtuel (`Xvfb`) et un navigateur basé sur WebKit. Vérifier sa disponibilité sur le serveur de production avant la bascule.

### Séparation local / prod dans `params.php`

Le paramètre `baseUrlGmap` doit pointer vers l'URL **interne** accessible par CutyCapt sur le serveur, pas l'URL publique. En production ce peut être `http://127.0.0.1` avec un alias VHost local si le port 80 n'est pas exposé publiquement.
