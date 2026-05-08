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
| Cache cartes JPG | `/cache/capture-gmap` |
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

## CutyCapt / Xvfb

| Paramètre | Valeur |
|---|---|
| cutycapt | `/usr/bin/cutycapt` |
| xvfb-run | `/usr/bin/xvfb-run` |
| Display dédié | `:10` (service systemd `xvfb-topoguide`) |
| Service | `/etc/systemd/system/xvfb-topoguide.service` |

⚠️ Ne pas utiliser le display `:99` (appartient à Tomcat8).

Service systemd à créer :
```ini
[Unit]
Description=Xvfb display :10 pour Topoguide CutyCapt
After=network.target

[Service]
ExecStart=/usr/bin/Xvfb :10 -screen 0 1240x877x24 -nolisten tcp
Restart=on-failure
User=www-data

[Install]
WantedBy=multi-user.target
```

Test manuel :
```bash
DISPLAY=:10 cutycapt \
  --url="http://topoguide.local/gmap/simple?lat=43.29&lon=-0.37&zoom=13" \
  --out=/tmp/test-carte.jpg \
  --delay=2000
```

---

## État de l'installation locale

| Fonctionnalité | État |
|---|---|
| Application Yii2 | ✅ Opérationnelle |
| Génération PDF FR/EN/ES | ✅ Opérationnelle |
| Interface admin (login/CRUD) | ✅ Opérationnelle |
| Pages cartes Leaflet (`/gmap/`) | ✅ Opérationnelle |
| Génération captures carte (CutyCapt) | ⚠️ Xvfb dédié à configurer |
| Polices Futura dans le PDF | ⚠️ À copier dans `fonts/` |
| Compte admin | ✅ Créé (user: admin) |

---

## À faire

- [ ] Créer le service systemd `xvfb-topoguide` (display `:10`)
- [ ] Mettre à jour `ScreenshotService` pour utiliser `DISPLAY=:10`
- [ ] Copier les polices Futura dans `/srv/topoguide/fonts/`
- [ ] Tester la génération de cartes JPG de bout en bout
- [ ] Tester le batch `php8.4 /srv/topoguide/yii screenshot/run`
- [ ] Configurer le cron (voir `synthese_technique_topoguide.md` §9)
- [ ] Confirmer version MariaDB locale
- [ ] Confirmer environnement recette (PHP, CutyCapt, SSL)
