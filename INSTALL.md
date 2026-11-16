# Vicidial Secure Access (lockdown): install

A user opens `access.php`, signs in with their VICIdial login, and their IP address is added to
the firewalld `trusted` zone for 7 days. After 7 days it is removed automatically and they sign in again.

Run every command as root. Do all of it on **every** server in the cluster.

## What is in this folder

| File | What it does |
|---|---|
| `access.php` | The page users open |
| `inc/code.php` | Checks the login, whitelists the IP |
| `inc/lib.php`, `inc/config.php` | Shared code and settings |
| `connecter.php` | Cluster sync: another server tells this one to allow an IP |
| `css/access.css`, `js/access.js`, `fonts/` | Page styling, script, fonts (all local) |
| `scripts/lockdown-allow` | Root helper that changes the firewall |
| `scripts/lockdown-expire` | Root helper, run hourly, removes expired IPs |

## 1. Web files

Copy the folder to the web root where the old version is (keep a backup of the old folder first).

## 2. Root helper scripts

```bash
install -o root -g root -m 755 scripts/lockdown-allow  /usr/local/sbin/lockdown-allow
install -o root -g root -m 755 scripts/lockdown-expire /usr/local/sbin/lockdown-expire
```

## 3. Data folder

`allowed.list` is written by root and read by the web server. `attempts/` is written by the web server.

```bash
mkdir -p /var/lib/lockdown/attempts
chown root:root /var/lib/lockdown
chmod 755 /var/lib/lockdown
chown apache:apache /var/lib/lockdown/attempts
chmod 700 /var/lib/lockdown/attempts
```

If SELinux is enforcing, the web server also needs permission to write there:

```bash
semanage fcontext -a -t httpd_sys_rw_content_t "/var/lib/lockdown/attempts(/.*)?"
restorecon -R /var/lib/lockdown
```

## 4. sudo: one command only

Create `/etc/sudoers.d/lockdown` (use `visudo -f /etc/sudoers.d/lockdown`):

```
apache ALL=(root) NOPASSWD: /usr/local/sbin/lockdown-allow
```

Then **remove the old rule** that let `apache` run `firewall-cmd` (or anything else) with sudo.
That old rule is what made the previous version dangerous.

## 5. Hourly expiry

Create `/etc/cron.d/lockdown`:

```
7 * * * * root /usr/local/sbin/lockdown-expire
```

## 6. Database login

Nothing to set up. The page reads the database login from `/etc/astguiclient.conf`, the same file
VICIdial uses. It only reads `vicidial_users` and `system_settings`; it never writes to the database.

The web server user must be able to read `/etc/astguiclient.conf` (it already can on a normal VICIdial install).

## 7. Cluster sync (skip on a single server)

1. Make one shared key and put the **same file** on every server:

   ```bash
   head -c 48 /dev/urandom | base64 > /etc/lockdown.key
   chown root:apache /etc/lockdown.key
   chmod 640 /etc/lockdown.key
   ```

2. On each server, edit `inc/config.php`:
   - `peers`: the `connecter.php` address of every **other** server.
   - `peer_ips`: the IP addresses the **other** servers call from.

`connecter.php` refuses everything that is not a signed POST from an address in `peer_ips`.

## 8. IPs that were already in the firewall

`lockdown-expire` only removes IPs that the new version added. Anything already in the `trusted`
zone is treated as added by an admin and is **never removed**, so office lines and the cluster
servers themselves stay safe.

That also means IPs whitelisted by the old version stay forever until you remove them once by hand:

```bash
firewall-cmd --permanent --zone=trusted --list-sources
firewall-cmd --permanent --zone=trusted --remove-source=<ip>   # for each old user IP
firewall-cmd --reload
```

## Settings (`inc/config.php`)

| Setting | Default | Meaning |
|---|---|---|
| `allow_days` | 7 | Days an IP stays whitelisted |
| `max_tries` | 5 | Wrong logins before an IP is locked out |
| `lock_minutes` | 15 | Length of the lockout |
| `min_user_level` | 1 | Lowest VICIdial user level that may use the page |
| `redirect_url` | empty | Where to go after access is granted (empty = this server's `/vicidial/welcome.php`) |

## Where to look when something fails

- Web side: the PHP error log, lines starting with `lockdown:`.
- Firewall side: `journalctl -t lockdown`.
- Current list: `cat /var/lib/lockdown/allowed.list` (IP, expiry as a Unix time or 0 for no expiry, user).
