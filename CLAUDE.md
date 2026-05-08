# Topoguide — Notes pour Claude Code

## Environnement local (machine de développement)

- **PHP CLI** : `php8.4` (ne pas utiliser `php` seul)
- **Composer** : `php8.4 /usr/local/bin/composer`
- **Migrations** : `php8.4 /home/srv/topoguide/yii migrate`
- **OS** : Debian/Ubuntu
- **Serveur web** : Apache 2.4
- **BDD** : MariaDB, base `topoguide`, user `topoguide`
- **Chemin app** : `/home/srv/topoguide/` (accessible via `/srv/topoguide`)

## Apache — point d'attention

Le vhost doit écouter sur `127.0.0.1:80` (pas `*:80`) car d'autres vhosts du serveur utilisent une IP explicite.
Config : `/etc/apache2/sites-available/topoguide-local.conf`

## Droits fichiers

Groupe `projetweb` (contient `www-data` + `triton`).
```bash
sudo chown -R www-data:projetweb /home/srv/topoguide/web/assets /home/srv/topoguide/runtime
sudo chmod -R 775 /home/srv/topoguide/web/assets /home/srv/topoguide/runtime
```

## Git

- **Dépôt** : `https://github.com/csT64/topoguide`
- **Branche de développement** : `claude/deploy-topoguide-taT2S`
- **Pull local** : `git pull origin claude/deploy-topoguide-taT2S`

## Référence complète

Voir `documentation/notes_environnement.md` pour tous les détails.
