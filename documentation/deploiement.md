# Déploiement — Topoguide

**Serveur cible :** 192.168.235.40  
**Domaine :** topoguide.aadt64.fr  
**DocumentRoot :** /srv/topoguide  
**OS :** Debian 11, Apache 2.4, PHP 8.1, MariaDB  

---

## 1. Prérequis système

```bash
apt install php8.1 php8.1-cli php8.1-fpm php8.1-mysql php8.1-xml \
            php8.1-mbstring php8.1-curl php8.1-gd php8.1-zip php8.1-intl \
            git unzip curl
```

---

## 2. Cloner les dépôts

```bash
cd /srv

# Application principale
git clone https://github.com/csT64/topoguide topoguide

# Composant client (si pas déjà présent)
git clone https://github.com/csT64/tourinsoft-client tourinsoft-client
```

---

## 3. Installer les dépendances PHP

```bash
cd /srv/topoguide
php8.1 /usr/local/bin/composer install --no-dev
```

> Composer crée automatiquement le symlink `vendor/adt64/tourinsoft-client → /srv/tourinsoft-client`
> grâce au `"type": "path"` dans `composer.json`.

---

## 4. Base de données

### 4a. Créer la base et l'utilisateur

```sql
CREATE DATABASE topoguide CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'topoguide'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE';
GRANT ALL PRIVILEGES ON topoguide.* TO 'topoguide'@'localhost';
FLUSH PRIVILEGES;
```

### 4b. Importer le schéma depuis le serveur source

```bash
# Sur le serveur source :
mysqldump -u topoguide -p topoguide > /tmp/topoguide_dump.sql

# Transférer vers le serveur cible :
scp /tmp/topoguide_dump.sql root@192.168.235.40:/tmp/

# Sur le serveur cible :
mysql -u topoguide -p topoguide < /tmp/topoguide_dump.sql
```

### 4c. Synchroniser l'historique des migrations Yii2

```bash
php8.1 /srv/topoguide/yii migrate --interactive=0
```

---

## 5. Configuration locale

Créer `/srv/topoguide/config/web-local.php` :

```php
<?php
return [
    'components' => [
        'request' => [
            'cookieValidationKey' => 'CLE_UNIQUE_A_GENERER',
        ],
        'db' => [
            'password' => 'MOT_DE_PASSE_BDD',
        ],
    ],
    'params' => [
        'wkhtmltopdf' => '/usr/local/bin/wkhtmltopdf',
        'weasyprint'  => '/usr/local/bin/weasyprint',
        'prince'      => '/usr/bin/prince',
        'pdfEngine'   => 'weasyprint',
    ],
];
```

> Générer une clé aléatoire : `php8.1 -r "echo bin2hex(random_bytes(32));"`

Créer `/srv/topoguide/config/db-local.php` si le mot de passe BDD est séparé,
ou l'inclure directement dans `web-local.php` comme ci-dessus.

---

## 6. Droits fichiers

```bash
chown -R www-data:projetweb /srv/topoguide
chmod -R 775 /srv/topoguide/web/assets /srv/topoguide/runtime
# Créer les dossiers runtime si absents
mkdir -p /srv/topoguide/runtime/logs /srv/topoguide/runtime/cache-gmap
chmod -R 775 /srv/topoguide/runtime
chown -R www-data:projetweb /srv/topoguide/runtime
```

---

## 7. Moteurs PDF

### wkhtmltopdf

```bash
# Vérifier s'il est déjà installé (présent pour test-app)
wkhtmltopdf --version
# Sinon :
wget https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bullseye_amd64.deb
dpkg -i wkhtmltox_0.12.6.1-3.bullseye_amd64.deb
apt-get install -f
```

### WeasyPrint

```bash
apt install python3-pip python3-cffi python3-brotli libpango-1.0-0 libpangoft2-1.0-0
pip3 install weasyprint
# Vérifier
weasyprint --version
```

### PrinceXML

```bash
wget https://www.princexml.com/download/prince_16.2-1_debian11_amd64.deb
apt install ./prince_16.2-1_debian11_amd64.deb
rm -f prince_16.2-1_debian11_amd64.deb
# Vérifier
prince --version
```

---

## 8. Certificat HTTPS (certbot + DNS challenge Gandi)

Serveur interne → HTTP challenge impossible → DNS challenge manuel.

```bash
certbot certonly --manual --preferred-challenges dns -d topoguide.aadt64.fr
```

Certbot affiche un enregistrement TXT à créer chez Gandi :
```
_acme-challenge.topoguide.aadt64.fr  TXT  "xxxxxxxxxxxxxxxx"
```

Attendre 1-2 min la propagation DNS, puis valider dans certbot.

Certificats générés dans :
- `/etc/letsencrypt/live/topoguide.aadt64.fr/fullchain.pem`
- `/etc/letsencrypt/live/topoguide.aadt64.fr/privkey.pem`

---

## 9. Vhost Apache

Créer `/etc/apache2/sites-available/topoguide.conf` :

```apache
<VirtualHost *:80>
    ServerName topoguide.aadt64.fr
    Redirect permanent / https://topoguide.aadt64.fr/
</VirtualHost>

<VirtualHost *:443>
    ServerName topoguide.aadt64.fr
    DocumentRoot /srv/topoguide/web/

    SSLEngine on
    SSLCertificateFile    /etc/ssl/topoguide/fullchain.pem
    SSLCertificateKeyFile /etc/ssl/topoguide/key.pem

    SetEnv APP_ENV production

    FallbackResource /index.php

    <Directory /srv/topoguide/web/>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog  /srv/topoguide/runtime/logs/apache-error.log
    CustomLog /srv/topoguide/runtime/logs/apache-access.log combined
</VirtualHost>
```

```bash
a2enmod ssl rewrite
a2ensite topoguide
apache2ctl configtest
systemctl reload apache2
```

---

## 10. Vérification finale

```bash
# Yii2 accessible
curl -I https://topoguide.aadt64.fr/admin

# PDF engines
wkhtmltopdf --version
weasyprint --version
prince --version

# Logs
tail -f /srv/topoguide/runtime/logs/apache-error.log
tail -f /srv/topoguide/runtime/logs/app.log
```

---

## Points d'attention

| Sujet | Note |
|-------|------|
| `web-local.php` | Ne pas versionner (déjà dans `.gitignore`) |
| `runtime/` et `web/assets/` | Doivent être writables par `www-data` |
| `tourinsoft-client` | Doit être cloné **avant** `composer install` |
| Migrations | Une seule migration existe (users) — le schéma complet vient du dump |
| `pdfEngine` dans params | Surcharger dans `web-local.php` selon les binaires disponibles |
| Carte cache gmap | Dossier `runtime/cache-gmap/` créé manuellement si absent |
