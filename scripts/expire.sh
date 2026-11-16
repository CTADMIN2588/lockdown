#!/bin/bash
# Lockdown hourly clean-up. Run by root from cron, every hour.
#
# Removes every IP whose time is up from the firewalld "trusted" zone. Only IPs that lockdown
# added with an expiry are touched; anything an admin added by hand (expiry 0, or not in the
# list at all) is left alone.

set -u
PATH=/usr/sbin:/usr/bin:/sbin:/bin
ZONE=trusted
DIR=/var/lib/lockdown
LIST=$DIR/allowed.list
LOCK=$DIR/.allowed.lock

[ -s "$LIST" ] && [ -e "$LOCK" ] || exit 0
firewall-cmd --state >/dev/null 2>&1 || exit 0          # firewalld not running: try again next hour
now=$(date +%s)

# The list is written by the web server, so every address is checked before it is used.
valid_ip() {
    local a="$1" o
    if [[ "$a" =~ ^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$ ]]; then
        for o in "${BASH_REMATCH[@]:1}"; do
            [ "$((10#$o))" -le 255 ] || return 1
        done
        return 0
    fi
    [[ "$a" == *:* && "$a" =~ ^[0-9A-Fa-f:]{2,39}$ ]]
}

(
    flock -x 9 || exit 1
    tmp=$(mktemp) || exit 1
    while read -r ip expires user; do
        [ -n "${ip:-}" ] || continue
        valid_ip "$ip" && [[ "${expires:-}" =~ ^[0-9]+$ ]] || continue   # damaged line: drop it, touch nothing
        if [ "$expires" -eq 0 ] || [ "$expires" -gt "$now" ]; then
            printf '%s %s %s\n' "$ip" "$expires" "${user:-}" >> "$tmp"
            continue
        fi
        firewall-cmd --zone="$ZONE" --remove-source="$ip" >/dev/null 2>&1
        firewall-cmd --permanent --zone="$ZONE" --remove-source="$ip" >/dev/null 2>&1
        if firewall-cmd --zone="$ZONE" --query-source="$ip" >/dev/null 2>&1 ||
           firewall-cmd --permanent --zone="$ZONE" --query-source="$ip" >/dev/null 2>&1; then
            printf '%s %s %s\n' "$ip" "$expires" "${user:-}" >> "$tmp"   # still there: try again next hour
            logger -t lockdown "could not remove expired $ip (${user:-})" 2>/dev/null
        else
            logger -t lockdown "expired $ip (${user:-})" 2>/dev/null
        fi
    done < "$LIST"
    cat "$tmp" > "$LIST"            # written in place, so the file stays owned by the web server
    rm -f "$tmp"
) 9>>"$LOCK"

# forget failed-login counters older than a day
find "$DIR/attempts" -type f -mmin +1440 -delete 2>/dev/null
exit 0
