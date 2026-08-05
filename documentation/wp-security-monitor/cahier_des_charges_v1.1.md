# WP Security Monitor

## Cahier des charges technique – Version 1.1

> **Historique des versions**
>
> | Version | Date | Modifications |
> |---------|------|---------------|
> | 1.0 | — | Version initiale |
> | 1.1 | 2026-08-05 | Élargissement du périmètre de fichiers (mu-plugins, drop-ins, racine, `.htaccess`/`.user.ini`, extensions PHP alternatives) ; exécution de WP-CLI en utilisateur non privilégié ; procédure de ré-initialisation de la référence ; auto-protection de l'outil ; normalisation du contrôle WP-Cron ; verrou d'exécution, rotation des logs et mécanisme d'envoi de mail spécifiés |

---

# 1. Objectif

Développer un outil léger de surveillance d'intégrité pour plusieurs sites WordPress hébergés sur un serveur Debian.

L'objectif est de détecter rapidement :

* modifications non autorisées de fichiers ;
* ajout de composants WordPress inconnus ;
* création de comptes administrateurs frauduleux ;
* modifications de permissions ;
* perte du durcissement appliqué au serveur.

Le script doit fonctionner en tâche cron quotidienne et générer une alerte uniquement en cas d'anomalie.

La fenêtre de détection assumée est de **24 heures** (fréquence du cron). Ce choix est un compromis volontaire entre réactivité et simplicité d'exploitation.

---

# 2. Contexte technique

## Serveur

* OS : Debian 12
* Serveur web : Apache 2.4
* PHP : PHP-FPM
* Base de données : MariaDB
* Exécution WordPress via Apache + PHP-FPM
* Administration système : utilisateur `aadt`
* Utilisateur d'exécution web : `www-data`

## Sites WordPress concernés

Installation WordPress classique (pas multisite WordPress).

Exemples :

```
/srv/www/pro_mono
/srv/www/fermewp_prod
```

Le script doit supporter plusieurs sites.

---

# 3. Principes de sécurité

## 3.1 Principe majeur

Les fichiers applicatifs WordPress ne doivent pas être modifiables par `www-data`.

Les mises à jour sont réalisées uniquement par l'administrateur système via WP-CLI.

Objectif :

Limiter la capacité d'un code PHP compromis à :

* modifier le cœur WordPress ;
* modifier les plugins ;
* modifier les thèmes ;
* déposer des backdoors persistantes.

## 3.2 Exécution de WP-CLI *(nouveau en 1.1)*

WP-CLI charge WordPress, donc potentiellement du code PHP compromis (plugins, thèmes).
Le script de monitoring tournant en root via cron, il ne doit **jamais** exécuter ce code avec ses propres privilèges.

Règles imposées :

* toutes les commandes WP-CLI sont exécutées via un utilisateur non privilégié :
  `sudo -u www-data wp ...` (utilisateur configurable) ;
* les options `--skip-plugins --skip-themes` sont systématiquement utilisées
  (les commandes de listing lisent le système de fichiers et la base de données,
  elles n'ont pas besoin de charger le code des extensions).

## 3.3 Auto-protection de l'outil *(nouveau en 1.1)*

Une référence falsifiable rend le dispositif inopérant. Exigences :

* le script (`/usr/local/sbin/wp-security-monitor.sh`), la configuration
  (`/etc/wp-security-monitor/`) et l'état de référence
  (`/var/lib/wp-security-monitor/`) appartiennent à `root` ;
* aucun de ces éléments n'est accessible en écriture au groupe ni aux autres
  (répertoires `750` maximum, fichiers `640` maximum, script `750`) ;
* à chaque exécution, le script vérifie lui-même ces permissions et lève une
  alerte en cas d'écart.

---

# 4. Technologie attendue

Langage :

* Bash compatible Debian 12

Dépendances autorisées :

* bash
* find
* sha256sum
* diff / comm
* stat
* awk
* sed
* sort
* grep
* flock (util-linux, présent de base sur Debian)
* wp-cli
* un agent d'envoi de mail en ligne de commande (voir § 11.2)

Aucune dépendance externe obligatoire.

---

# 5. Architecture du script

Nom :

```
wp-security-monitor.sh
```

Deux modes obligatoires :

## Initialisation

Création de l'état de référence :

```
wp-security-monitor.sh init            # tous les sites + référence système
wp-security-monitor.sh init <site>     # un seul site (chemin ou nom)
```

L'`init` ciblé par site est **obligatoire** : il est le support de la procédure
de mise à jour décrite au § 12.

## Contrôle

Comparaison avec la référence :

```
wp-security-monitor.sh check           # tous les sites
wp-security-monitor.sh check <site>    # un seul site
```

## Verrou d'exécution *(nouveau en 1.1)*

Le script pose un verrou (`flock`) pour empêcher deux exécutions concurrentes
(cron + lancement manuel). Une exécution qui trouve le verrou posé s'arrête
immédiatement avec un message explicite.

---

# 6. Configuration

Le script ne doit contenir aucune donnée spécifique en dur.

Fichier :

```
/etc/wp-security-monitor/config.conf
```

Exemple :

```bash
SITES=(
"/srv/www/pro_mono"
"/srv/www/fermewp_prod"
)

STATE_DIR="/var/lib/wp-security-monitor"

LOG_DIR="/var/log/wp-security-monitor"

MAIL_TO="administrateur@example.com"

# Utilisateur non privilégié pour l'exécution de WP-CLI (§ 3.2)
WP_CLI_USER="www-data"

# Utilisateurs dont les crontabs sont surveillées (§ 8.9)
CRON_USERS=("root" "aadt" "www-data")

# Seuil "changement massif" de fichiers (§ 9)
MASS_CHANGE_THRESHOLD=20
```

---

# 7. Stockage des références

Structure :

```
/var/lib/wp-security-monitor/

pro_mono/

    hashes.txt        # empreintes SHA256 des fichiers surveillés
    uploads.txt       # inventaire des fichiers sensibles dans uploads
    plugins.txt
    themes.txt
    admins.txt
    permissions.txt
    versions.txt
    wpcron.txt

fermewp_prod/

    ...

system/

    syscron.txt       # référence cron système (commune au serveur)
```

Le nom du répertoire d'état est le nom de base du chemin du site
(`/srv/www/pro_mono` → `pro_mono`). Les chemins de sites doivent donc avoir
des noms de base uniques.

---

# 8. Contrôles à réaliser

## 8.1 Intégrité des fichiers

Calcul SHA256 des fichiers sensibles.

Périmètre surveillé *(élargi en 1.1)* :

```
wp-config.php
*.php à la racine du site            (index.php, wp-login.php, wp-settings.php, ...)
wp-admin/**/*.php
wp-includes/**/*.php
wp-content/plugins/**/*.php
wp-content/themes/**/*.php
wp-content/mu-plugins/**/*.php       (chargés automatiquement, jamais listés comme "actifs")
wp-content/*.php                     (drop-ins : object-cache.php, db.php, advanced-cache.php, ...)
.htaccess et .user.ini               (sur toute l'arborescence du site)
```

Les extensions PHP alternatives sont incluses partout où du PHP est surveillé :
`.php`, `.php5`, `.php7`, `.phtml`, `.pht`, `.phar`.

Justification des ajouts 1.1 :

* `mu-plugins` et les drop-ins sont chargés automatiquement par WordPress sans
  activation : ce sont des emplacements classiques de persistance de backdoors ;
* un `.htaccess` ou `.user.ini` déposé par un attaquant peut rendre exécutables
  des fichiers d'extension quelconque (y compris dans `uploads/`) ;
* les fichiers PHP de la racine (`wp-login.php`, `index.php`) sont des cibles
  fréquentes d'injection.

Une modification déclenche une alerte. Un fichier ajouté ou supprimé dans ce
périmètre déclenche également une alerte.

## 8.2 Détection de nouveaux fichiers dans uploads

Surveiller :

```
wp-content/uploads
```

Règles :

* tout nouveau fichier d'extension PHP (y compris alternatives : `.phtml`,
  `.php5`, `.php7`, `.pht`, `.phar`) = **alerte critique** ;
* tout nouveau fichier `.htaccess` ou `.user.ini` = **alerte critique** ;

Ne pas analyser le contenu des fichiers.

Justification :

Éviter les faux positifs liés aux fonctions PHP légitimes.

*(Note 1.1 : la détection de nouveaux fichiers PHP dans `plugins/`, `themes/`
et `mu-plugins/` est couverte par le § 8.1 — un fichier ajouté apparaît dans le
diff des empreintes.)*

## 8.3 Contrôle des plugins WordPress

Utiliser WP-CLI (selon § 3.2) :

```
sudo -u www-data wp plugin list --skip-plugins --skip-themes
```

Comparer avec la référence.

Détecter :

* nouveau plugin ;
* plugin supprimé ;
* changement actif/inactif ;
* changement de version.

## 8.4 Contrôle des thèmes

Utiliser :

```
sudo -u www-data wp theme list --skip-plugins --skip-themes
```

Détecter :

* nouveau thème ;
* suppression ;
* changement de thème actif.

## 8.5 Contrôle des administrateurs WordPress

Surveiller :

* utilisateurs ayant le rôle administrateur ;
* nouveaux comptes ;
* suppression de comptes ;
* modification des comptes (login, email).

Déclencher une alerte critique en cas de nouvel administrateur.

## 8.6 Contrôle des permissions

Surveiller (propriétaire, groupe, permissions Unix) :

```
wp-admin
wp-includes
wp-content/plugins
wp-content/themes
wp-content/mu-plugins
wp-content/uploads
wp-config.php
```

Détecter notamment :

```
www-data:www-data
```

sur des dossiers applicatifs normalement administrés par `aadt`.

En complément *(nouveau en 1.1)* : recenser récursivement les fichiers
appartenant à `www-data` dans les répertoires applicatifs (`wp-admin`,
`wp-includes`, `plugins`, `themes`, `mu-plugins`). Toute apparition d'un
fichier appartenant à `www-data` dans ces répertoires = **alerte critique**
(perte du durcissement, cf. § 3.1).

## 8.7 Contrôle versions

Surveiller :

* version WordPress ;
* version PHP CLI ;
* version(s) PHP-FPM installée(s).

Une modification doit être signalée.

## 8.8 Contrôle WP-Cron

Via WP-CLI (selon § 3.2) :

```
sudo -u www-data wp cron event list --skip-plugins --skip-themes
```

*(précisé en 1.1)* La comparaison porte sur la **liste normalisée des hooks**
(noms d'événements, dédupliqués et triés), et non sur les horaires de prochaine
exécution — ceux-ci changent en permanence et rendraient le contrôle inexploitable.

Détecter :

* nouvel événement cron (nouveau hook) ;
* disparition d'un hook.

## 8.9 Contrôle cron système

Surveiller :

```
/etc/crontab
/etc/cron.d/*
/etc/cron.hourly/*  /etc/cron.daily/*  /etc/cron.weekly/*  /etc/cron.monthly/*
crontabs des utilisateurs listés dans CRON_USERS
```

Comparer avec une référence (empreintes des fichiers + contenu des crontabs
utilisateur).

---

# 9. Niveau de criticité

## Critique (code retour 2)

* nouveau compte administrateur ;
* fichier PHP, `.htaccess` ou `.user.ini` nouveau dans `uploads` ;
* changement massif de fichiers surveillés (seuil : `MASS_CHANGE_THRESHOLD`,
  20 par défaut) ;
* fichier ou dossier applicatif passé en propriété `www-data` ;
* nouveau plugin inconnu ;
* nouveau fichier dans `mu-plugins` ou nouveau drop-in dans `wp-content/`.

## Important (code retour 1)

* modification de fichier surveillé (sous le seuil massif) ;
* modification / suppression / changement d'état d'un plugin ;
* modification de thème ou changement de thème actif ;
* suppression ou modification d'un compte administrateur ;
* changement de version WordPress ;
* changement de permissions sur le périmètre § 8.6 ;
* échec d'exécution de WP-CLI sur un site (le site ne peut plus être contrôlé) ;
* permissions incorrectes sur l'outil lui-même (§ 3.3).

## Information (code retour 1)

* changement cron (WP-Cron ou cron système) ;
* changement version PHP.

---

# 10. Rapport

Sortie console :

Exemple :

```
[OK] pro_mono

Fichiers : OK
Uploads : OK
Plugins : OK
Themes : OK
Admins : OK
Permissions : OK
Versions : OK
WP-Cron : OK
```

En cas d'anomalie :

```
ALERTE

Site : pro_mono

[CRITIQUE] Nouveau plugin :
xxxx

[CRITIQUE] Nouvel administrateur :
xxxx

[IMPORTANT] Fichier modifié :
xxxx
```

Chaque section d'anomalie est limitée aux 20 premières entrées, avec mention
du nombre total si la liste est tronquée (le détail complet est dans le log).

---

# 11. Exploitation

## 11.1 Cron

Exemple :

```
30 3 * * * /usr/local/sbin/wp-security-monitor.sh check
```

Le script :

* écrit un log ;
* envoie un mail uniquement en cas d'anomalie ;
* retourne un code système exploitable.

Codes :

```
0 : aucun problème
1 : anomalie mineure (important / information)
2 : anomalie critique
```

## 11.2 Envoi de mail *(précisé en 1.1)*

Le mécanisme d'envoi doit être **vérifié à l'installation** : sur un Debian
minimal, aucun MTA n'est configuré par défaut et une alerte qui ne part pas
est le pire scénario silencieux.

* commande utilisée : `mail` (bsd-mailx / s-nail) via un MTA local configuré
  (`msmtp-mta` recommandé pour un simple relais SMTP) ;
* si la commande d'envoi échoue, le script le consigne dans le log et sur
  stderr (le code retour reste celui de l'anomalie détectée) ;
* un test d'envoi doit faire partie de la procédure d'installation.

## 11.3 Logs *(précisé en 1.1)*

* log d'exécution : `/var/log/wp-security-monitor/wp-security-monitor.log`
  (horodaté, mode append) ;
* dernier rapport complet : `/var/log/wp-security-monitor/last-report.txt` ;
* rotation via `logrotate` (fichier `/etc/logrotate.d/wp-security-monitor`,
  rotation hebdomadaire, 12 rotations conservées).

---

# 12. Procédure de mise à jour de la référence *(nouveau en 1.1)*

Toute mise à jour légitime (cœur, plugin, thème) modifie des fichiers surveillés
et déclenchera des alertes au prochain `check`. La ré-initialisation de la
référence est un **acte volontaire et conscient**, jamais un réflexe :

1. l'administrateur effectue la mise à jour via WP-CLI (conformément au § 3.1) ;
2. il exécute immédiatement `wp-security-monitor.sh check <site>` et **vérifie
   que les écarts détectés correspondent exactement à la mise à jour effectuée** ;
3. il ré-initialise la référence du site concerné uniquement :
   `wp-security-monitor.sh init <site>`.

Règle d'exploitation : ne **jamais** ré-initialiser une référence pour faire
taire une alerte dont l'origine n'est pas identifiée et expliquée. Une
ré-initialisation aveugle validerait un état potentiellement compromis.

C'est ce workflow qui protège l'outil contre la fatigue d'alerte : les alertes
restent rares car chaque mise à jour est suivie d'une ré-initialisation ciblée.

---

# 13. Limites

Le script ne cherche pas :

* les signatures de malware ;
* les fonctions PHP dangereuses ;
* les contenus obfusqués ;
* les backdoors stockées en base de données (widgets, options, contenu injecté) —
  le contrôle des comptes administrateurs (§ 8.5) couvre le vecteur principal.

La stratégie retenue est :

"Détecter les changements anormaux plutôt que chercher des signatures d'attaque."

Le contenu des fichiers de `uploads/` n'est pas hashé (volumétrie) : seule
l'apparition de fichiers à risque y est détectée (§ 8.2).

---

# 14. Évolutions possibles

* intégration Zabbix ;
* export JSON ;
* support TYPO3 / PrestaShop / Yii2 ;
* historique des changements ;
* interface web de consultation.

*(Note 1.1 : chaque extension de périmètre devra être arbitrée — la valeur de
l'outil tient à sa simplicité ; le support multi-CMS transformerait un script
simple en produit à maintenir.)*
