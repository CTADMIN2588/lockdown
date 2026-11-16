# Vicidial Secure Access (lockdown): install

Users open `http://<server>:81/access.php` and sign in with their VICIdial login. Their IP is added
to the firewalld `trusted` zone for 7 days; after that it is removed and they sign in again.
In a cluster, one sign-in on any server allows the IP on every server.

Run everything as root.

## Install (each server)

**1. Copy the folder** (the old version is kept in `/root`):

```bash
[ -d /var/www/lockdown ] && mv /var/www/lockdown /root/lockdown_old_$(date +%F); cd /var/www && curl -sL https://github.com/CTADMIN2588/lockdown/archive/refs/heads/main.tar.gz | tar xz && mv lockdown-main lockdown
```

**2. Run the setup:**

```bash
bash /var/www/lockdown/setup.sh
```

It sets everything up and checks it. Fix anything it marks `FIX`, then run it again.
Port 81 must serve `/var/www/lockdown`, as it did for the old version.

## Cluster

On **every** server, put the same list of all servers (that server included) in
`/var/www/lockdown/inc/config.php`, one per line, like the curl lines in the old `code.php`:

```php
'cluster_servers' => array(
    'db-splendid',
    'web-splendid',
),
```

Then run `bash /var/www/lockdown/setup.sh` again on each server: it checks that every server in the list answers.

The database login comes from `/etc/astguiclient.conf`, so the Asterisk servers need no `dbconnect.php` edit.
On the database server, the `trusted` zone must include every cluster server's IP, or they cannot
reach the database once firewalld is running.

## When something fails

- Run `bash /var/www/lockdown/setup.sh`: it says what is wrong.
- The reason for a failed sign-in: `grep "lockdown:" /var/log/php-fpm/www-error.log | tail -5`
- Who is allowed: `cat /var/lib/lockdown/allowed.list` (IP, expiry as a Unix time or 0 = never, user)

## Settings (`inc/config.php`)

| Setting | Default | Meaning |
|---|---|---|
| `cluster_servers` | empty | All cluster servers, same list on every server |
| `allow_days` | 7 | Days an IP stays allowed |
| `max_tries` / `lock_minutes` | 5 / 15 | Wrong logins before an IP is locked out, and for how long |
| `min_user_level` | 1 | Lowest VICIdial user level that may sign in |
| `redirect_url` | empty | Where to go after access is granted (empty = this server's `/vicidial/welcome.php`) |

IPs that were in the `trusted` zone before lockdown (offices, cluster servers, IPs added by the old
version) are never removed. Remove old user IPs once by hand:
`firewall-cmd --permanent --zone=trusted --remove-source=<ip> && firewall-cmd --reload`
