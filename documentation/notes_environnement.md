# Notes d'environnement — Référence système

> Document de référence opérationnel. À consulter avant toute commande système et à mettre à jour dès qu'un nouvel élément impacte l'environnement.

---

## Installation sur un nouveau serveur

Procédure complète pour déployer l'application from scratch sur Debian 11/12.

### 1. Prérequis système

```bash
# PHP 8.4
sudo apt install php8.4 php8.4-cli php8.4-fpm php8.4-mysql php8.4-gd \
     php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-intl

# Composer
curl -sS https://getcomposer.org/installer | php8.4
sudo mv composer.phar /usr/local/bin/composer

# Apache + MariaDB
sudo apt install apache2 mariadb-server

# Modules Apache
sudo a2enmod rewrite
```

### 2. wkhtmltopdf (version avec patched qt — obligatoire)

> ⚠️ La version des dépôts Debian (`apt install wkhtmltopdf`) est **sans patched qt** et ne supporte pas les headers/footers. Utiliser le `.deb` officiel.

```bash
# Debian 11 (bullseye)
wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bullseye_amd64.deb
sudo apt install ./wkhtmltox_0.12.6.1-3.bullseye_amd64.deb

# Debian 12 (bookworm)
wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_amd64.deb
sudo apt install ./wkhtmltox_0.12.6.1-3.bookworm_amd64.deb

# Vérifier
/usr/local/bin/wkhtmltopdf --version
# → doit afficher : wkhtmltopdf 0.12.6.1 (with patched qt)
```

Le binaire s'installe dans `/usr/local/bin/wkhtmltopdf` — déjà référencé dans `config/params.php`.

### 3. Cloner le dépôt

```bash
cd /home/srv
git clone https://github.com/csT64/topoguide.git topoguide
ln -s /home/srv/topoguide /srv/topoguide

# Créer le groupe et droits
sudo groupadd projetweb
sudo usermod -aG projetweb www-data
sudo usermod -aG projetweb $(whoami)
sudo chown -R $(whoami):projetweb /home/srv/topoguide
```

### 4. Dépendances PHP (Composer)

```bash
cd /srv/topoguide
php8.4 /usr/local/bin/composer install --no-dev
```

Le script post-install copie automatiquement les fichiers Leaflet dans `web/gmap/`.

### 5. Fichiers Leaflet supplémentaires

```bash
curl -sLo /srv/topoguide/web/gmap/leaflet.gpx.js \
  https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.7.0/gpx.min.js

curl -sLo /srv/topoguide/web/gmap/leaflet.kml.js \
  https://raw.githubusercontent.com/windycom/leaflet-kml/master/L.KML.js
```

### 6. Base de données

```sql
CREATE DATABASE topoguide CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'topoguide'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE';
GRANT ALL PRIVILEGES ON topoguide.* TO 'topoguide'@'localhost';
FLUSH PRIVILEGES;
```

Créer `config/db.php` (non versionné) :

```php
<?php
return [
    'class'    => 'yii\db\Connection',
    'dsn'      => 'mysql:host=localhost;dbname=topoguide',
    'username' => 'topoguide',
    'password' => 'MOT_DE_PASSE',
    'charset'  => 'utf8mb4',
];
```

### 7. Configuration application

Dans `config/web.php`, renseigner la `cookieValidationKey` (clé aléatoire propre à chaque serveur, ne jamais committer) :

```php
'cookieValidationKey' => 'GENERER_UNE_CLE_ALEATOIRE_ICI',
```

Générer une clé :
```bash
php8.4 -r "echo bin2hex(random_bytes(32));"
```

### 8. Droits fichiers

```bash
sudo chown -R www-data:projetweb /srv/topoguide/runtime /srv/topoguide/web/assets /srv/topoguide/web/producteur
sudo chmod -R 775 /srv/topoguide/runtime /srv/topoguide/web/assets /srv/topoguide/web/producteur
```

### 9. Apache VHost

```apache
<VirtualHost 127.0.0.1:80>
    ServerName topoguide.local
    DocumentRoot /srv/topoguide/web

    <Directory /srv/topoguide/web>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

```bash
sudo a2ensite topoguide-local
sudo systemctl reload apache2
```

### 10. Migrations

```bash
php8.4 /srv/topoguide/yii migrate
```

### 11. Vérification

| URL | Résultat attendu |
|---|---|
| `http://topoguide.local/` | Page d'accueil |
| `http://topoguide.local/admin` | Interface admin (login) |
| `http://topoguide.local/topoguide/fr/ID_ITINERAIRE` | Fiche HTML publique |
| `http://topoguide.local/topoguide/fr/ID_ITINERAIRE.pdf` | PDF généré par wkhtmltopdf |

---

## PHP

| Environnement | Version installée | Binaire CLI |
|---|---|---|
| Local (Debian 11) | 8.4 | `php8.4` |
| Recette | À confirmer | À confirmer |
| Production | À confirmer | À confirmer |

**Important** : ne pas utiliser `php` seul en CLI — appeler explicitement la version :

```bash
php8.4 /usr/local/bin/composer install
php8.4 /srv/topoguide/yii migrate
php8.4 /srv/topoguide/yii screenshot/run
```

---

## Composer

| Paramètre | Valeur |
|---|---|
| Chemin binaire | `/usr/local/bin/composer` |
| Appel | `php8.4 /usr/local/bin/composer` |

---

## Git

| Paramètre | Valeur |
|---|---|
| Dépôt | `https://github.com/csT64/topoguide` |
| Branche de développement | `claude/deploy-topoguide-taT2S` |
| Pull local | `git pull origin claude/deploy-topoguide-taT2S` |

### Tags de version

| Tag | Description |
|---|---|
| `v1` | Première version stable — PDF TCPDF, cartes JPG, admin, filtres GridView |

### Récupérer la version V1

```bash
git checkout v1                          # lecture seule
git checkout claude/deploy-topoguide-taT2S  # revenir en dev
```

> ⚠️ En mode `detached HEAD` après `git checkout v1`, les commits ne sont pas possibles.

---

## Serveur web

| Paramètre | Valeur |
|---|---|
| Logiciel | Apache 2.4 |
| Document root | `/srv/topoguide/web` |
| Config VHost | `/etc/apache2/sites-available/topoguide-local.conf` |
| Point d'attention | `<VirtualHost 127.0.0.1:80>` (pas `*:80`) |

---

## Base de données

| Paramètre | Valeur |
|---|---|
| Logiciel | MariaDB |
| Base | `topoguide` |
| Utilisateur | `topoguide` |
| Host | `localhost` |
| Config app | `/srv/topoguide/config/db.php` (non versionné) |

---

## Chemins

| Élément | Chemin |
|---|---|
| Application | `/srv/topoguide` (symlink → `/home/srv/topoguide`) |
| Cache cartes JPG | `/srv/topoguide/runtime/cache-gmap/` |
| Cache tuiles OSM | `/srv/topoguide/runtime/cache-tiles/` |
| Logs Yii2 | `/srv/topoguide/runtime/logs/` |
| Polices Futura | `/srv/topoguide/fonts/` |
| Logos producteurs | `/srv/topoguide/web/producteur/` |
| Assets PDF | `/srv/topoguide/web/pix/pdf/` |
| Logos départements | `/srv/topoguide/web/pix/pdf/departement/` |

---

## Dépendances Composer notables

| Package | Version | Rôle |
|---|---|---|
| `yiisoft/yii2-bootstrap5` | `~2.0.0` | UI Bootstrap 5 |
| `bower-asset/leaflet` | `^1.9` | Cartes Leaflet |
| `tecnickcom/tcpdf` | `~6.7.0` | Présent mais non utilisé (remplacé par wkhtmltopdf) |

---

## Génération PDF (`TopoguideService`)

Le PDF est généré via **wkhtmltopdf 0.12.6.1 (with patched qt)** à partir du template HTML `views/topoguide/fiche.php`.

| Élément | Valeur |
|---|---|
| Binaire | `/usr/local/bin/wkhtmltopdf` (param `wkhtmltopdf` dans `config/params.php`) |
| Template | `views/topoguide/fiche.php` (`$forPdf=true`) |
| Header | `buildHeaderHtml()` — `haut_page.png` + picto difficulté |
| Footer | `buildFooterHtml()` — `pied_page_noir.png` + adresse producteur |
| Marges | Top 27mm, Bottom 22mm, Left/Right 0 (fond perdu) |

### Pictos PDF

wkhtmltopdf ne rend pas les SVG via `<img>`. Chaque picto doit exister en `.png` dans `web/pix/pdf/` pour s'afficher dans le PDF. La version HTML utilise le `.svg` en priorité.

---

## Droits fichiers

| Dossier | Propriétaire | Groupe | Chmod |
|---|---|---|---|
| `web/assets` | `www-data` | `projetweb` | `775` |
| `runtime` | `www-data` | `projetweb` | `775` |
| `web/producteur` | `www-data` | `projetweb` | `775` |

Groupe `projetweb` : contient `www-data` et l'utilisateur de session.

---

## Génération des cartes JPG (`StaticMapService`)

Service PHP+GD sans navigateur — `components/map/StaticMapService.php` :

1. Télécharge le tracé GPX/KML depuis le CDN TourInSoft
2. Calcule la bounding box + marge 15%
3. Choisit le zoom OSM optimal (4→16)
4. Assemble les tuiles OSM (cachées dans `runtime/cache-tiles/`)
5. Dessine tracé + marqueurs épingles
6. Enregistre JPEG 85% dans `runtime/cache-gmap/{id}.jpg`

```bash
php8.4 /srv/topoguide/yii screenshot/run          # toutes les cartes manquantes
php8.4 /srv/topoguide/yii screenshot/one ID       # une carte spécifique
rm -rf /srv/topoguide/runtime/cache-tiles/        # purger le cache tuiles OSM
```

---

## Proxy CORS GPX/KML

`GmapController::actionProxy()` — contourne l'absence d'en-têtes CORS du CDN TourInSoft.
Hôtes autorisés : `cdt64.media.tourinsoft.eu`, `api.tourisme64.com`, `api.adt64.fr`.

```
GET /gmap/proxy?url=https://cdt64.media.tourinsoft.eu/upload/fichier.gpx
```

---

## État des fonctionnalités

| Fonctionnalité | État |
|---|---|
| Application Yii2 | ✅ Opérationnelle |
| Génération PDF FR/EN/ES (wkhtmltopdf) | ✅ Opérationnelle |
| Page HTML publique accessible (RGAA) | ✅ Opérationnelle |
| Interface admin (login/CRUD) | ✅ Opérationnelle |
| Filtres GridView admin | ✅ Opérationnels |
| Pages cartes Leaflet (`/gmap/`) | ✅ Opérationnelle |
| Génération cartes JPG (StaticMapService) | ✅ Opérationnelle |
| Éditeur de carte admin | ✅ Opérationnel |
| Proxy CORS GPX/KML | ✅ Opérationnel |
| Header/footer PDF répétés chaque page | ✅ Opérationnel (wkhtmltopdf patched qt) |
| Polices Futura dans le PDF | ⚠️ À copier dans `fonts/` |
| Compte admin | ✅ Créé (user: admin) |

---

## À faire

- [ ] Copier les polices Futura dans `/srv/topoguide/fonts/`
- [ ] Configurer le cron pour `screenshot/run`
- [ ] Confirmer version MariaDB et environnement recette
- [ ] Ajouter `picto-112.png` dans `web/pix/pdf/` (SVG non rendu par wkhtmltopdf)
