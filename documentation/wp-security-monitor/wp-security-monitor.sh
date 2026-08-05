#!/usr/bin/env bash
#
# wp-security-monitor.sh — surveillance d'intégrité de sites WordPress
#
# Conforme au cahier des charges v1.1.
#
# Usage :
#   wp-security-monitor.sh init  [site]   # crée l'état de référence (tous les sites ou un seul)
#   wp-security-monitor.sh check [site]   # compare l'état courant à la référence
#
# [site] : chemin complet (/srv/www/pro_mono) ou nom de base (pro_mono).
#
# Codes retour : 0 = aucun problème, 1 = anomalie mineure, 2 = anomalie critique.

set -u

CONFIG_FILE="${WPSM_CONFIG:-/etc/wp-security-monitor/config.conf}"
SCRIPT_PATH="$(readlink -f "$0")"

# Valeurs par défaut, surchargées par le fichier de configuration
STATE_DIR="/var/lib/wp-security-monitor"
LOG_DIR="/var/log/wp-security-monitor"
MAIL_TO=""
MAIL_CMD="mail"
WP_CLI_USER="www-data"
WP_CLI_BIN="wp"
CRON_USERS=("root")
MASS_CHANGE_THRESHOLD=20
SITES=()

# Extensions exécutables surveillées (cf. § 8.1 du cahier des charges)
PHP_EXT_REGEX='.*\.(php[0-9]?|phtml|pht|phar)$'

# ---------------------------------------------------------------------------
# Infrastructure : configuration, verrou, log, rapport
# ---------------------------------------------------------------------------

die() { echo "ERREUR : $*" >&2; exit 2; }

load_config() {
    [ -r "$CONFIG_FILE" ] || die "configuration introuvable : $CONFIG_FILE"
    # shellcheck source=/dev/null
    . "$CONFIG_FILE"
    [ "${#SITES[@]}" -gt 0 ] || die "aucun site défini dans $CONFIG_FILE (variable SITES)"
    mkdir -p "$STATE_DIR" "$LOG_DIR"
    chmod 750 "$STATE_DIR" "$LOG_DIR" 2>/dev/null
}

acquire_lock() {
    exec 9>"$STATE_DIR/.lock"
    flock -n 9 || die "une autre exécution est déjà en cours (verrou : $STATE_DIR/.lock)"
}

LOG_FILE=""
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') $*" >>"$LOG_FILE"
}

# Rapport : findings[] contient "SEVERITE|site|titre|fichier_détail"
declare -a FINDINGS=()
EXIT_CODE=0

# add_finding <CRITIQUE|IMPORTANT|INFORMATION> <site> <titre> [fichier de détail]
add_finding() {
    local sev="$1" site="$2" title="$3" detail="${4:-}"
    FINDINGS+=("$sev|$site|$title|$detail")
    case "$sev" in
        CRITIQUE) EXIT_CODE=2 ;;
        *)        [ "$EXIT_CODE" -lt 1 ] && EXIT_CODE=1 ;;
    esac
    log "[$sev] $site : $title"
}

# ---------------------------------------------------------------------------
# Auto-protection (§ 3.3) : permissions du script, de la conf et de l'état
# ---------------------------------------------------------------------------

self_check() {
    local path
    for path in "$SCRIPT_PATH" "$CONFIG_FILE" "$STATE_DIR"; do
        [ -e "$path" ] || continue
        local owner perms
        owner=$(stat -c '%U' "$path")
        perms=$(stat -c '%a' "$path")
        if [ "$owner" != "root" ]; then
            add_finding IMPORTANT "outil" "$path n'appartient pas à root (propriétaire : $owner)"
        fi
        # Écriture groupe (020) ou autres (002) interdite
        if [ $(( 0$perms & 022 )) -ne 0 ]; then
            add_finding IMPORTANT "outil" "$path est modifiable par le groupe ou les autres (permissions : $perms)"
        fi
    done
}

# ---------------------------------------------------------------------------
# WP-CLI en utilisateur non privilégié (§ 3.2)
# ---------------------------------------------------------------------------

wp_run() {
    local site="$1"; shift
    if [ -n "$WP_CLI_USER" ] && [ "$(id -un)" != "$WP_CLI_USER" ]; then
        sudo -n -u "$WP_CLI_USER" -- "$WP_CLI_BIN" --path="$site" \
            --skip-plugins --skip-themes "$@" 2>/dev/null
    else
        "$WP_CLI_BIN" --path="$site" --skip-plugins --skip-themes "$@" 2>/dev/null
    fi
}

# ---------------------------------------------------------------------------
# Collecte : chaque fonction écrit l'état courant d'un contrôle sur stdout
# ---------------------------------------------------------------------------

# § 8.1 — empreintes SHA256 du périmètre applicatif
collect_hashes() {
    local site="$1" d
    {
        find "$site" -maxdepth 1 -type f -regextype posix-extended -iregex "$PHP_EXT_REGEX" -print0 2>/dev/null
        for d in wp-admin wp-includes wp-content/plugins wp-content/themes wp-content/mu-plugins; do
            [ -d "$site/$d" ] && find "$site/$d" -type f -regextype posix-extended -iregex "$PHP_EXT_REGEX" -print0 2>/dev/null
        done
        # Drop-ins : PHP directement sous wp-content/
        [ -d "$site/wp-content" ] && find "$site/wp-content" -maxdepth 1 -type f -regextype posix-extended -iregex "$PHP_EXT_REGEX" -print0 2>/dev/null
        # .htaccess et .user.ini sur toute l'arborescence (uploads compris)
        find "$site" -type f \( -name '.htaccess' -o -name '.user.ini' \) -print0 2>/dev/null
    } | sort -zu | xargs -0 -r sha256sum
}

# § 8.2 — inventaire des fichiers à risque dans uploads (chemins seuls)
collect_uploads() {
    local site="$1" up="$1/wp-content/uploads"
    [ -d "$up" ] || return 0
    find "$up" -type f -regextype posix-extended \
        \( -iregex "$PHP_EXT_REGEX" -o -name '.htaccess' -o -name '.user.ini' \) 2>/dev/null | sort
}

# § 8.3 / 8.4 / 8.5 — inventaires WP-CLI (CSV trié, clé en premier champ)
collect_plugins() { wp_run "$1" plugin list --fields=name,status,version --format=csv | tail -n +2 | sort; }
collect_themes()  { wp_run "$1" theme  list --fields=name,status,version --format=csv | tail -n +2 | sort; }
collect_admins()  { wp_run "$1" user   list --role=administrator --fields=ID,user_login,user_email --format=csv | tail -n +2 | sort; }

# § 8.6 — permissions du périmètre + fichiers www-data dans les répertoires applicatifs
collect_permissions() {
    local site="$1" p d
    echo "# permissions"
    for p in wp-admin wp-includes wp-content/plugins wp-content/themes wp-content/mu-plugins wp-content/uploads wp-config.php; do
        [ -e "$site/$p" ] && stat -c '%U:%G %a %n' "$site/$p"
    done
    echo "# fichiers appartenant à ${WP_CLI_USER:-www-data} dans les répertoires applicatifs"
    for d in wp-admin wp-includes wp-content/plugins wp-content/themes wp-content/mu-plugins; do
        [ -d "$site/$d" ] && find "$site/$d" -user "${WP_CLI_USER:-www-data}" 2>/dev/null
    done | sort | sed 's/^/PROPRIETE-WEB: /'
}

# § 8.7 — versions WordPress / PHP CLI / PHP-FPM
collect_versions() {
    local site="$1"
    echo "wordpress: $(wp_run "$site" core version || echo 'INDISPONIBLE')"
    echo "php-cli: $(php -v 2>/dev/null | head -n1 || echo 'INDISPONIBLE')"
    local fpm
    fpm=$(ls -d /etc/php/*/fpm 2>/dev/null | awk -F/ '{print $4}' | sort | tr '\n' ' ')
    echo "php-fpm: ${fpm:-INDISPONIBLE}"
}

# § 8.8 — hooks WP-Cron normalisés (noms seuls, dédupliqués)
collect_wpcron() {
    wp_run "$1" cron event list --fields=hook --format=csv | tail -n +2 | sort -u
}

# § 8.9 — cron système (empreintes des fichiers + crontabs utilisateur)
collect_syscron() {
    local f u
    echo "# fichiers cron"
    for f in /etc/crontab /etc/cron.d/* /etc/cron.hourly/* /etc/cron.daily/* /etc/cron.weekly/* /etc/cron.monthly/*; do
        [ -f "$f" ] && sha256sum "$f"
    done | sort -k2
    echo "# crontabs utilisateur"
    for u in "${CRON_USERS[@]}"; do
        echo "## $u"
        crontab -l -u "$u" 2>/dev/null | grep -v '^#' | sed '/^[[:space:]]*$/d'
    done
}

# ---------------------------------------------------------------------------
# Comparaison
# ---------------------------------------------------------------------------

# Diff des empreintes : produit "A <chemin>", "M <chemin>", "D <chemin>"
# (le chemin commence au 67e caractère : 64 hex + 2 espaces)
diff_hashes() {
    awk 'NR==FNR { ref[substr($0,67)] = $1; next }
         { p = substr($0,67)
           if (!(p in ref))        print "A " p
           else if (ref[p] != $1)  print "M " p
           delete ref[p] }
         END { for (p in ref) print "D " p }' "$1" "$2" | sort
}

# Diff d'inventaires CSV (clé = 1er champ) : NOUVEAU / MODIFIE / SUPPRIME
diff_inventory() {
    awk -F, 'NR==FNR { ref[$1] = $0; next }
             { if (!($1 in ref))        print "NOUVEAU: " $0
               else if (ref[$1] != $0)  print "MODIFIE: " ref[$1] " -> " $0
               delete ref[$1] }
             END { for (k in ref) print "SUPPRIME: " ref[k] }' "$1" "$2" | sort
}

# Lignes présentes dans le courant mais pas dans la référence (fichiers triés)
diff_added() { comm -13 "$1" "$2"; }

# ---------------------------------------------------------------------------
# init / check
# ---------------------------------------------------------------------------

site_state_dir() { echo "$STATE_DIR/$(basename "$1")"; }

init_site() {
    local site="$1" sdir
    sdir=$(site_state_dir "$site")
    [ -d "$site" ] || die "site introuvable : $site"
    mkdir -p "$sdir"
    chmod 750 "$sdir"

    collect_hashes      "$site" >"$sdir/hashes.txt"
    collect_uploads     "$site" >"$sdir/uploads.txt"
    collect_plugins     "$site" >"$sdir/plugins.txt"
    collect_themes      "$site" >"$sdir/themes.txt"
    collect_admins      "$site" >"$sdir/admins.txt"
    collect_permissions "$site" >"$sdir/permissions.txt"
    collect_versions    "$site" >"$sdir/versions.txt"
    collect_wpcron      "$site" >"$sdir/wpcron.txt"
    chmod 640 "$sdir"/*.txt

    if [ ! -s "$sdir/plugins.txt" ] && [ ! -s "$sdir/admins.txt" ]; then
        echo "AVERTISSEMENT : WP-CLI n'a rien retourné pour $site — vérifier sudo -u $WP_CLI_USER $WP_CLI_BIN" >&2
    fi
    echo "Référence initialisée : $site ($(wc -l <"$sdir/hashes.txt") fichiers suivis)"
    log "init $site : référence créée"
}

init_system() {
    mkdir -p "$STATE_DIR/system"
    chmod 750 "$STATE_DIR/system"
    collect_syscron >"$STATE_DIR/system/syscron.txt"
    chmod 640 "$STATE_DIR/system/syscron.txt"
    echo "Référence cron système initialisée"
    log "init system : référence cron créée"
}

TMP_DIR=""

# Résumé borné d'un fichier de détail (20 premières lignes)
detail_excerpt() {
    local f="$1" total
    total=$(wc -l <"$f")
    head -n 20 "$f"
    [ "$total" -gt 20 ] && echo "... ($total entrées au total, détail complet dans le log)"
}

check_site() {
    local site="$1" sdir status_ok=()
    sdir=$(site_state_dir "$site")
    local name; name=$(basename "$site")

    if [ ! -d "$sdir" ]; then
        add_finding IMPORTANT "$name" "aucune référence : lancer 'wp-security-monitor.sh init $site'"
        return
    fi
    if [ ! -d "$site" ]; then
        add_finding CRITIQUE "$name" "répertoire du site introuvable : $site"
        return
    fi

    # Fichiers de travail propres à ce site (les findings gardent une référence
    # vers ces fichiers : ils ne doivent pas être écrasés par le site suivant)
    local T="$TMP_DIR/$name"

    # --- § 8.1 Fichiers ---
    collect_hashes "$site" >"$T.hashes.cur"
    diff_hashes "$sdir/hashes.txt" "$T.hashes.cur" >"$T.hashes.diff"
    if [ -s "$T.hashes.diff" ]; then
        local n_changed n_crit
        n_changed=$(wc -l <"$T.hashes.diff")
        # Ajouts dans les zones critiques : mu-plugins, drop-ins, .htaccess/.user.ini
        grep '^A ' "$T.hashes.diff" | sed 's/^A //' \
            | grep -E "/wp-content/mu-plugins/|/wp-content/[^/]+\.(php[0-9]?|phtml|pht|phar)$|/\.htaccess$|/\.user\.ini$" \
            >"$T.hashes.crit" || true
        n_crit=$(wc -l <"$T.hashes.crit")
        if [ "$n_changed" -ge "$MASS_CHANGE_THRESHOLD" ]; then
            add_finding CRITIQUE "$name" "changement massif de fichiers surveillés ($n_changed écarts)" "$T.hashes.diff"
        elif [ "$n_crit" -gt 0 ]; then
            add_finding CRITIQUE "$name" "nouveau fichier dans une zone critique (mu-plugins / drop-in / .htaccess / .user.ini)" "$T.hashes.crit"
            add_finding IMPORTANT "$name" "fichiers surveillés modifiés ($n_changed écarts)" "$T.hashes.diff"
        else
            add_finding IMPORTANT "$name" "fichiers surveillés modifiés/ajoutés/supprimés ($n_changed écarts)" "$T.hashes.diff"
        fi
    else
        status_ok+=("Fichiers")
    fi

    # --- § 8.2 Uploads ---
    collect_uploads "$site" >"$T.uploads.cur"
    diff_added "$sdir/uploads.txt" "$T.uploads.cur" >"$T.uploads.diff"
    if [ -s "$T.uploads.diff" ]; then
        add_finding CRITIQUE "$name" "fichier PHP / .htaccess / .user.ini nouveau dans uploads" "$T.uploads.diff"
    else
        status_ok+=("Uploads")
    fi

    # --- § 8.3 Plugins ---
    collect_plugins "$site" >"$T.plugins.cur"
    if [ ! -s "$T.plugins.cur" ] && [ -s "$sdir/plugins.txt" ]; then
        add_finding IMPORTANT "$name" "WP-CLI n'a pas pu lister les plugins (site indisponible ou sudo refusé)"
    else
        diff_inventory "$sdir/plugins.txt" "$T.plugins.cur" >"$T.plugins.diff"
        if [ -s "$T.plugins.diff" ]; then
            if grep -q '^NOUVEAU:' "$T.plugins.diff"; then
                add_finding CRITIQUE "$name" "nouveau plugin détecté" "$T.plugins.diff"
            else
                add_finding IMPORTANT "$name" "changement sur les plugins (version / état / suppression)" "$T.plugins.diff"
            fi
        else
            status_ok+=("Plugins")
        fi
    fi

    # --- § 8.4 Thèmes ---
    collect_themes "$site" >"$T.themes.cur"
    diff_inventory "$sdir/themes.txt" "$T.themes.cur" >"$T.themes.diff"
    if [ -s "$T.themes.diff" ]; then
        add_finding IMPORTANT "$name" "changement sur les thèmes" "$T.themes.diff"
    else
        status_ok+=("Themes")
    fi

    # --- § 8.5 Administrateurs ---
    collect_admins "$site" >"$T.admins.cur"
    if [ ! -s "$T.admins.cur" ] && [ -s "$sdir/admins.txt" ]; then
        add_finding IMPORTANT "$name" "WP-CLI n'a pas pu lister les administrateurs"
    else
        diff_inventory "$sdir/admins.txt" "$T.admins.cur" >"$T.admins.diff"
        if [ -s "$T.admins.diff" ]; then
            if grep -q '^NOUVEAU:' "$T.admins.diff"; then
                add_finding CRITIQUE "$name" "nouveau compte administrateur WordPress" "$T.admins.diff"
            else
                add_finding IMPORTANT "$name" "modification/suppression d'un compte administrateur" "$T.admins.diff"
            fi
        else
            status_ok+=("Admins")
        fi
    fi

    # --- § 8.6 Permissions ---
    collect_permissions "$site" >"$T.perms.cur"
    diff "$sdir/permissions.txt" "$T.perms.cur" >"$T.perms.diff" 2>&1
    if [ -s "$T.perms.diff" ]; then
        # Toute nouvelle ligne mentionnant l'utilisateur web = perte de durcissement
        if grep '^>' "$T.perms.diff" | grep -v '^> #' | grep -Eq "PROPRIETE-WEB:|${WP_CLI_USER:-www-data}"; then
            add_finding CRITIQUE "$name" "durcissement perdu : fichier/dossier applicatif passé en propriété www-data" "$T.perms.diff"
        else
            add_finding IMPORTANT "$name" "changement de permissions détecté" "$T.perms.diff"
        fi
    else
        status_ok+=("Permissions")
    fi

    # --- § 8.7 Versions ---
    collect_versions "$site" >"$T.versions.cur"
    diff "$sdir/versions.txt" "$T.versions.cur" >"$T.versions.diff" 2>&1
    if [ -s "$T.versions.diff" ]; then
        if grep -q '^[<>] wordpress:' "$T.versions.diff"; then
            add_finding IMPORTANT "$name" "changement de version WordPress" "$T.versions.diff"
        else
            add_finding INFORMATION "$name" "changement de version PHP" "$T.versions.diff"
        fi
    else
        status_ok+=("Versions")
    fi

    # --- § 8.8 WP-Cron ---
    collect_wpcron "$site" >"$T.wpcron.cur"
    diff "$sdir/wpcron.txt" "$T.wpcron.cur" >"$T.wpcron.diff" 2>&1
    if [ -s "$T.wpcron.diff" ]; then
        add_finding INFORMATION "$name" "changement des hooks WP-Cron" "$T.wpcron.diff"
    else
        status_ok+=("WP-Cron")
    fi

    # Résumé console pour le site
    if [ "${#status_ok[@]}" -eq 8 ]; then
        echo "[OK] $name"
    fi
}

check_system() {
    local ref="$STATE_DIR/system/syscron.txt"
    collect_syscron >"$TMP_DIR/syscron.cur"
    if [ ! -f "$ref" ]; then
        add_finding IMPORTANT "system" "aucune référence cron système : lancer 'wp-security-monitor.sh init'"
        return
    fi
    diff "$ref" "$TMP_DIR/syscron.cur" >"$TMP_DIR/syscron.diff" 2>&1
    if [ -s "$TMP_DIR/syscron.diff" ]; then
        add_finding INFORMATION "system" "changement du cron système" "$TMP_DIR/syscron.diff"
    else
        echo "[OK] cron système"
    fi
}

# ---------------------------------------------------------------------------
# Rapport et alerte mail (§ 10, § 11)
# ---------------------------------------------------------------------------

emit_report() {
    local report="$LOG_DIR/last-report.txt"
    {
        echo "wp-security-monitor — $(date '+%Y-%m-%d %H:%M:%S') — $(hostname)"
        echo
        if [ "${#FINDINGS[@]}" -eq 0 ]; then
            echo "Aucune anomalie détectée."
        else
            echo "ALERTE — ${#FINDINGS[@]} anomalie(s) détectée(s)"
            echo
            local f sev site title detail
            for f in "${FINDINGS[@]}"; do
                IFS='|' read -r sev site title detail <<<"$f"
                echo "[$sev] Site : $site"
                echo "$title"
                if [ -n "$detail" ] && [ -s "$detail" ]; then
                    detail_excerpt "$detail" | sed 's/^/    /'
                    # Détail complet dans le log
                    { echo "--- détail [$sev] $site : $title ---"; cat "$detail"; } >>"$LOG_FILE"
                fi
                echo
            done
        fi
    } | tee "$report"
    chmod 640 "$report" 2>/dev/null

    if [ "$EXIT_CODE" -gt 0 ] && [ -n "$MAIL_TO" ]; then
        local subject="[wp-security-monitor] $(hostname) : "
        [ "$EXIT_CODE" -eq 2 ] && subject+="ALERTE CRITIQUE" || subject+="anomalie détectée"
        if ! $MAIL_CMD -s "$subject" "$MAIL_TO" <"$report" 2>>"$LOG_FILE"; then
            echo "ERREUR : envoi du mail d'alerte à $MAIL_TO impossible (voir $LOG_FILE)" >&2
            log "échec envoi mail à $MAIL_TO"
        fi
    fi
}

# ---------------------------------------------------------------------------
# Point d'entrée
# ---------------------------------------------------------------------------

usage() {
    sed -n '3,12p' "$SCRIPT_PATH" | sed 's/^# \{0,1\}//'
    exit 2
}

resolve_site() {
    # Accepte un chemin complet ou un nom de base présent dans SITES
    local arg="$1" s
    for s in "${SITES[@]}"; do
        if [ "$s" = "$arg" ] || [ "$(basename "$s")" = "$arg" ]; then
            echo "$s"; return 0
        fi
    done
    return 1
}

main() {
    local mode="${1:-}" target="${2:-}"
    [ "$mode" = "init" ] || [ "$mode" = "check" ] || usage

    load_config
    acquire_lock
    LOG_FILE="$LOG_DIR/wp-security-monitor.log"
    touch "$LOG_FILE" && chmod 640 "$LOG_FILE"
    log "=== démarrage : mode=$mode cible=${target:-tous} ==="

    local sites=("${SITES[@]}")
    if [ -n "$target" ]; then
        local resolved
        resolved=$(resolve_site "$target") || die "site inconnu : $target (non listé dans SITES)"
        sites=("$resolved")
    fi

    TMP_DIR=$(mktemp -d /tmp/wpsm.XXXXXX)
    trap 'rm -rf "$TMP_DIR"' EXIT

    case "$mode" in
        init)
            local s
            for s in "${sites[@]}"; do init_site "$s"; done
            # La référence système n'est régénérée que sur un init global
            [ -z "$target" ] && init_system
            ;;
        check)
            self_check
            local s
            for s in "${sites[@]}"; do check_site "$s"; done
            [ -z "$target" ] && check_system
            emit_report
            ;;
    esac

    log "=== fin : code retour $EXIT_CODE ==="
    exit "$EXIT_CODE"
}

main "$@"
