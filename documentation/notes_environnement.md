# Notes d'environnement — Référence système

> Document de référence opérationnel. À consulter avant toute commande système et à mettre à jour dès qu'un nouvel élément impacte l'environnement.

---

## PHP

| Environnement | Version installée | Binaire CLI |
|---|---|---|
| Local (Debian) | 8.4 | `php8.4` |
| Recette | À confirmer | À confirmer |
| Production | À confirmer | À confirmer |

**Important** : ne pas utiliser `php` seul en CLI — appeler explicitement la version :

```bash
# Composer
php8.4 /usr/local/bin/composer install
php8.4 /usr/local/bin/composer update

# Migrations Yii2
php8.4 /srv/topoguide/yii migrate

# Batch screenshot
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
| Propriété du dossier | `sudo chown -R triton:projetweb /home/srv/topoguide` |

---

## Serveur web

| Paramètre | Valeur |
|---|---|
| Logiciel | Apache 2.4 |
| Document root | `/srv/topoguide/web` |
| Config VHost local | `/etc/apache2/sites-available/topoguide-local.conf` |
| ServerName local | `topoguide.local` |
| Point d'attention | Utiliser `<VirtualHost 127.0.0.1:80>` (pas `*:80`) — autres vhosts sur IP explicite |

---

## Base de données

| Paramètre | Valeur |
|---|---|
| Logiciel | MariaDB |
| Version | À confirmer |
| Base | `topoguide` |
| Utilisateur | `topoguide` |
| Host | `localhost` |
| Config app | `/srv/topoguide/config/db.php` |

---

## Chemins

| Élément | Chemin local |
|---|---|
| Application | `/srv/topoguide` (symlink → `/home/srv/topoguide`) |
| Cache cartes JPG | `@runtime/cache-gmap` → `/srv/topoguide/runtime/cache-gmap/` |
| Cache tuiles OSM | `@runtime/cache-tiles` → `/srv/topoguide/runtime/cache-tiles/` |
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
| `bower-asset/leaflet` | `^1.9` | Cartes Leaflet (CSS + images copiés dans `web/gmap/`) |
| `tecnickcom/tcpdf` | `~6.7.0` | Génération PDF |

Après `composer install`, le script `publish-leaflet` copie automatiquement `leaflet.css` et `images/` dans `web/gmap/`. Le fichier `leaflet.js` est téléchargé via `curl` depuis unpkg.com.

---

## Droits fichiers

| Dossier | Propriétaire | Groupe | Chmod |
|---|---|---|---|
| `web/assets` | `www-data` | `projetweb` | `775` |
| `runtime` | `www-data` | `projetweb` | `775` |
| `web/producteur` | `www-data` | `projetweb` | `775` |

Groupe `projetweb` : contient `www-data` et l'utilisateur de session `triton`.

---

## Génération des cartes JPG (`StaticMapService`)

Les cartes sont générées **entièrement en PHP+GD**, sans navigateur. Le service `components/map/StaticMapService.php` :

1. **Télécharge le tracé** depuis le CDN TourInSoft (GPX ou KML selon le champ renseigné)
2. **Calcule la bounding box** de l'ensemble tracé + marqueurs, avec marge de 15 %
3. **Choisit le niveau de zoom** OSM optimal (zoom 4 à 16) pour que le contenu occupe ≤ 80 % du canvas
4. **Assemble les tuiles OSM** (`https://tile.openstreetmap.org/{z}/{x}/{y}.png`) — 256×256 px chacune, mises en cache dans `@runtime/cache-tiles/`
5. **Dessine le tracé** par-dessus les tuiles
6. **Dessine les marqueurs** façon Google Maps (épingle teardrops)
7. **Enregistre** le JPEG 85 % dans `@runtime/cache-gmap/{id}.jpg`

### Personnalisation du style

Tout le style visuel est dans `StaticMapService.php`, méthodes `drawTrack()` et `drawPin()` :

| Élément | Méthode | Paramètre à modifier |
|---|---|---|
| Couleur du tracé | `drawTrack()` | `imagecolorallocate($this->img, 15, 50, 140)` — RGB bleu foncé |
| Épaisseur du tracé | `drawTrack()` | `imagesetthickness($this->img, 3)` — valeur en pixels |
| Épaisseur du contour blanc | `drawTrack()` | `imagesetthickness($this->img, 6)` |
| Couleur marqueur départ (D) | `drawPin()` | `imagecolorallocate($this->img, 45, 136, 45)` — RGB vert |
| Couleur marqueurs étapes | `drawPin()` | `imagecolorallocate($this->img, 210, 35, 35)` — RGB rouge |
| Taille de l'épingle | `drawPin()` | `$r = 15` (rayon cercle), `$tail = 18` (hauteur queue) |
| Taille du texte dans l'épingle | `drawPinLabel()` | `$size = 9.0` (2 chiffres) / `11.0` (1 chiffre) |

### Commandes

```bash
# Générer toutes les cartes manquantes ou obsolètes
php8.4 /srv/topoguide/yii screenshot/run

# Régénérer une carte spécifique
php8.4 /srv/topoguide/yii screenshot/one ITIAQU000V505GSD
```

### Cache tuiles OSM

Les tuiles téléchargées sont conservées dans `runtime/cache-tiles/z/x/y.png`. Elles n'expirent pas automatiquement — purger manuellement si les fonds de carte paraissent obsolètes :

```bash
rm -rf /srv/topoguide/runtime/cache-tiles/
```

## Fichiers statiques Leaflet (`web/gmap/`)

Ces fichiers ne sont **pas** dans le dépôt git (dossier `web/gmap/` ignoré). Ils doivent être présents sur chaque serveur.

| Fichier | Origine |
|---|---|
| `leaflet.js` | Généré par `composer install` (script `publish-leaflet` via unpkg.com) |
| `leaflet_min.css` | Généré par `composer install` (copié depuis `vendor/bower-asset/leaflet/dist/`) |
| `images/` | Généré par `composer install` |
| `leaflet.gpx.js` | À télécharger manuellement |
| `leaflet.kml.js` | À télécharger manuellement |

```bash
# Après composer install (leaflet.js/css/images sont déjà copiés)
curl -sLo /srv/topoguide/web/gmap/leaflet.gpx.js \
  https://cdnjs.cloudflare.com/ajax/libs/leaflet-gpx/1.7.0/gpx.min.js

curl -sLo /srv/topoguide/web/gmap/leaflet.kml.js \
  https://raw.githubusercontent.com/windycom/leaflet-kml/master/L.KML.js
```

---

## Proxy CORS pour GPX/KML (`/gmap/proxy`)

Le CDN TourInSoft (`cdt64.media.tourinsoft.eu`) ne renvoie pas d'en-têtes CORS.
Les fichiers GPX/KML ne peuvent donc pas être chargés directement par le navigateur
depuis les pages admin.

`GmapController::actionProxy()` récupère le fichier côté serveur PHP et le sert
localement avec `Access-Control-Allow-Origin: *`. Seuls les hôtes de la liste blanche
sont autorisés (`cdt64.media.tourinsoft.eu`, `api.tourisme64.com`, `api.adt64.fr`).

URL d'appel : `GET /gmap/proxy?url=https://cdt64.media.tourinsoft.eu/upload/fichier.gpx`

---

## Interface d'administration

### Liste des itinéraires (`/admin/itineraire`)

- Colonnes filtrables : **ID**, **Titre**, **Commune départ**, **Auteur**, **Locomotion**, **Difficulté**
- Colonnes triables (flèches ↑↓) : ID, Titre, Commune départ, Auteur
- Bouton **Carte ✎** → ouvre l'éditeur de carte de l'itinéraire

### Éditeur de carte (`/admin/itineraire/carte?id=XXX`)

Vue dédiée à la gestion des marqueurs de carte :

- **Carte JPG actuelle** avec boutons Supprimer / Régénérer
- **Carte Leaflet interactive** avec :
  - Tracé GPX ou KML affiché (chargé via le proxy CORS)
  - Marqueurs glissables (vert = Départ, rouge = Étapes numérotées)
  - Mise à jour automatique des champs coordonnées lors du glisser
- **Formulaire** : coordonnées départ + tableau des étapes (lat/lon éditables)
- **Sauvegarde** : met à jour le JSON `etapes` en base et régénère la carte JPG

---

| Fonctionnalité | État |
|---|---|
| Application Yii2 | ✅ Opérationnelle |
| Génération PDF FR/EN/ES | ✅ Opérationnelle |
| Interface admin (login/CRUD) | ✅ Opérationnelle |
| Pages cartes Leaflet (`/gmap/`) | ✅ Opérationnelle |
| Génération cartes JPG (StaticMapService) | ✅ Opérationnelle — PHP+GD, tracé GPX/KML, marqueurs épingles |
| Carte dans le PDF | ✅ Opérationnelle |
| Éditeur de carte admin | ✅ Opérationnel — Leaflet interactif, marqueurs glissables, tracé GPX/KML |
| Proxy CORS GPX/KML | ✅ Opérationnel |
| Polices Futura dans le PDF | ⚠️ À copier dans `fonts/` |
| Compte admin | ✅ Créé (user: admin) |

---

## À faire

- [ ] Copier les polices Futura dans `/srv/topoguide/fonts/`
- [ ] Configurer le cron pour `screenshot/run` (voir `synthese_technique_topoguide.md` §9)
- [ ] Confirmer version MariaDB locale
- [ ] Confirmer environnement recette (PHP, versions, SSL)
