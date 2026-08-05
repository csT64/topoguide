# WP Security Monitor

Outil de surveillance d'intégrité pour sites WordPress sur Debian 12,
conforme au [cahier des charges v1.1](cahier_des_charges_v1.1.md).

Principe : détecter les changements anormaux par rapport à un état de
référence (fichiers, plugins, thèmes, administrateurs, permissions, versions,
cron), plutôt que chercher des signatures d'attaque.

## Installation

```bash
# 1. Script
install -o root -g root -m 750 wp-security-monitor.sh /usr/local/sbin/wp-security-monitor.sh

# 2. Configuration
install -d -o root -g root -m 750 /etc/wp-security-monitor
install -o root -g root -m 640 config.conf.example /etc/wp-security-monitor/config.conf
vi /etc/wp-security-monitor/config.conf   # adapter SITES, MAIL_TO, CRON_USERS

# 3. Vérifier l'envoi de mail (msmtp-mta recommandé comme relais SMTP)
echo "test wp-security-monitor" | mail -s "test alerte" administrateur@example.com

# 4. Créer l'état de référence
wp-security-monitor.sh init

# 5. Cron quotidien
echo '30 3 * * * root /usr/local/sbin/wp-security-monitor.sh check' > /etc/cron.d/wp-security-monitor

# 6. Rotation des logs
cat > /etc/logrotate.d/wp-security-monitor <<'EOF'
/var/log/wp-security-monitor/wp-security-monitor.log {
    weekly
    rotate 12
    compress
    missingok
    notifempty
}
EOF
```

## Utilisation

```bash
wp-security-monitor.sh check              # contrôle de tous les sites + cron système
wp-security-monitor.sh check pro_mono     # contrôle d'un seul site
wp-security-monitor.sh init pro_mono      # ré-initialise la référence d'un site
```

Codes retour : `0` aucun problème, `1` anomalie mineure, `2` anomalie critique.

## Après une mise à jour légitime (procédure § 12)

1. Mise à jour via WP-CLI par l'administrateur système.
2. `wp-security-monitor.sh check <site>` — **vérifier que les écarts détectés
   correspondent exactement à la mise à jour effectuée**.
3. `wp-security-monitor.sh init <site>` — ré-initialisation ciblée.

Ne jamais ré-initialiser une référence pour faire taire une alerte inexpliquée.
