#!/bin/bash
# Lockdown setup. Run once on each server, as root, after copying the folder:
#
#     bash /var/www/lockdown/setup.sh
#
# Run it again any time (after an update, or after editing the cluster list): it repeats the
# setup and checks that everything works.

set -u
HERE=$(cd "$(dirname "$0")" && pwd)
DATA=/var/lib/lockdown
FW=/usr/bin/firewall-cmd
problems=0
ok()   { echo "  OK    $*"; }
bad()  { echo "  FIX   $*"; problems=$((problems + 1)); }

[ "$(id -u)" -eq 0 ] || { echo "Run this as root."; exit 1; }

# The user PHP runs as (apache on a normal VICIdial server).
WEB=$(ps -eo user=,comm= | awk '$2 ~ /^php-fpm/ && $1 != "root" { print $1; exit }')
[ -n "$WEB" ] || WEB=$(ps -eo user=,comm= | awk '$2 ~ /^(httpd|apache2)$/ && $1 != "root" { print $1; exit }')
[ -n "$WEB" ] || WEB=apache
id "$WEB" >/dev/null 2>&1 || { echo "Web server user '$WEB' not found."; exit 1; }

echo "Setting up lockdown in $HERE (web server user: $WEB)"

# 1. Data folder: the allow list (read by the hourly clean-up) and the failed-login counters.
mkdir -p "$DATA/attempts"
touch "$DATA/allowed.list" "$DATA/.allowed.lock"
chown -R "$WEB:$WEB" "$DATA"
chmod 750 "$DATA" "$DATA/attempts"
chmod 640 "$DATA/allowed.list" "$DATA/.allowed.lock"
if command -v getenforce >/dev/null 2>&1 && [ "$(getenforce)" = Enforcing ]; then
    chcon -R -t httpd_sys_rw_content_t "$DATA" 2>/dev/null
fi

# 2. sudo: the web server may run firewall-cmd as root (the old version needed the same).
echo "$WEB ALL=(root) NOPASSWD: $FW" > /etc/sudoers.d/lockdown.new
chmod 440 /etc/sudoers.d/lockdown.new
if visudo -cf /etc/sudoers.d/lockdown.new >/dev/null 2>&1; then
    mv -f /etc/sudoers.d/lockdown.new /etc/sudoers.d/lockdown
else
    rm -f /etc/sudoers.d/lockdown.new
    bad "could not write the sudo rule /etc/sudoers.d/lockdown"
fi

# 3. Hourly clean-up of expired IPs.
chmod 755 "$HERE/scripts/expire.sh"
echo "7 * * * * root /bin/bash $HERE/scripts/expire.sh" > /etc/cron.d/lockdown
chmod 644 /etc/cron.d/lockdown

# 4. Helpers of the previous lockdown version are no longer used.
rm -f /usr/local/sbin/lockdown-allow /usr/local/sbin/lockdown-expire

# ---------------------------------------------------------------- checks
echo
echo "Checks:"

if command -v php >/dev/null 2>&1; then
    errors=0
    for f in "$HERE"/*.php "$HERE"/inc/*.php; do
        php -l "$f" >/dev/null 2>&1 || { bad "PHP error in $f:  $(php -l "$f" 2>&1 | head -1)"; errors=1; }
    done
    [ "$errors" -eq 0 ] && ok "PHP files have no syntax errors"
fi

if systemctl is-active --quiet firewalld; then
    ok "firewalld is running"
else
    bad "firewalld is NOT running. Lockdown cannot allow anyone until it runs, and this server has no firewall."
    echo "        First make sure the other cluster servers are in the trusted zone:"
    echo "          firewall-offline-cmd --zone=trusted --list-sources"
    echo "        then start it:  systemctl enable --now firewalld"
fi
systemctl is-enabled --quiet firewalld || bad "firewalld will not start after a reboot (systemctl enable firewalld)"

if sudo -u "$WEB" sudo -n "$FW" --state >/dev/null 2>&1; then
    ok "the web server can run firewall-cmd"
elif systemctl is-active --quiet firewalld; then
    bad "the web server cannot run firewall-cmd through sudo"
fi

if command -v php >/dev/null 2>&1; then
    if sudo -u "$WEB" php -r 'require $argv[1] . "/inc/lib.php"; exit(ld_db() ? 0 : 1);' "$HERE" >/dev/null 2>&1; then
        ok "database connection works"
    else
        bad "cannot connect to the database: $(sudo -u "$WEB" php -r 'require $argv[1] . "/inc/lib.php"; ld_db();' "$HERE" 2>&1 | grep -o 'lockdown: .*' | head -1)"
        echo "        On a cluster: on the database server, the trusted zone must include this server's IP."
    fi
fi

code=$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:81/access.php")
if [ "$code" = 200 ]; then
    ok "the page answers on port 81"
else
    bad "http://127.0.0.1:81/access.php answers $code (expected 200): port 81 must serve $HERE"
fi

# Cluster: every listed server must answer connecter.php. 400 = reachable and accepts this server.
if command -v php >/dev/null 2>&1; then
    urls=$(php -r 'require $argv[1] . "/inc/lib.php"; foreach (ld_cluster_servers() as $s) { echo $s["url"], "\n"; }' "$HERE" 2>/dev/null)
    if [ -z "$urls" ]; then
        echo "  --    single server (cluster_servers in inc/config.php is empty)"
    else
        for url in $urls; do
            reply=$(curl -s -m 6 -X POST -w ' %{http_code}' "$url")
            case "$reply" in
                *' 400') ok "cluster: $url" ;;
                *' 403') bad "cluster: $url refuses this server. Its cluster_servers list must include this server" ;;
                *)       bad "cluster: $url not reachable (answer: ${reply:- none}). Check the name, port 81 and the firewall there" ;;
            esac
        done
    fi
fi

echo
if [ "$problems" -eq 0 ]; then
    echo "Done. Open http://<this server>:81/access.php and sign in."
else
    echo "Done, with $problems thing(s) to fix (marked FIX above)."
fi
