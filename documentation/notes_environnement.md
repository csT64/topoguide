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
| Config VHost local | À créer dans `/etc/apache2/sites-available/` |

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
| Application | `/srv/topoguide` |
| Cache cartes JPG | `/cache/capture-gmap` |
| Logs Yii2 | `/srv/topoguide/runtime/logs/` |
| Polices Futura | `/srv/topoguide/fonts/` |
| Logos producteurs | `/srv/topoguide/web/producteur/` |

---

## Dépendances Composer notables

| Package | Version | Rôle |
|---|---|---|
| `yiisoft/yii2-bootstrap5` | `~2.0.0` | UI Bootstrap 5 (requis, absent du template de base) |

---

## Droits fichiers

| Dossier | Propriétaire | Groupe | Chmod |
|---|---|---|---|
| `web/assets` | `www-data` | `projetweb` | `775` |
| `runtime` | `www-data` | `projetweb` | `775` |

Groupe `projetweb` : contient `www-data` et l'utilisateur de session `triton`.

---

## À confirmer / À faire

- [ ] Version MariaDB locale
- [ ] Version PHP sur recette et production
- [ ] Présence de CutyCapt / Xvfb sur recette et production
- [ ] Polices Futura (`futuramediumbt.ttf`, `FuturaHeavyfont.ttf`) à copier dans `fonts/`
- [x] VHost Apache local créé : `/etc/apache2/sites-available/topoguide-local.conf`, activé avec `a2ensite`
      - Utiliser `<VirtualHost 127.0.0.1:80>` (pas `*:80`) car d'autres vhosts écoutent sur `127.0.0.1:80`
- [ ] Cron à configurer (voir `documentation/synthese_technique_topoguide.md` §9)
