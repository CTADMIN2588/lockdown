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

Copy the folder to `/var/www/lockdown` (keep a backup of the old folder first). The commands below assume that path.

## 2. Root helper scripts

```bash
install -o root -g root -m 755 /var/www/lockdown/scripts/lockdown-allow  /usr/local/sbin/lockdown-allow
install -o root -g root -m 755 /var/www/lockdown/scripts/lockdown-expire /usr/local/sbin/lockdown-expire
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

A sign-in on any server whitelists the IP on every server, the same way the old `code.php` did
with one `curl` call per server.

On **every** server, put the same list of all cluster servers (that server included) in
`/var/www/lockdown/inc/config.php`:

```php
'cluster_servers' => array('10.0.0.11', '10.0.0.12', '10.0.0.13'),
```

- Use the addresses the servers reach each other on (internal IPs if they have them).
- Each server is called at `http://<server>:81/connecter.php`. If the lockdown folder is served
  on another port, change `cluster_port`.
- `connecter.php` only accepts calls from a server in the list. Take a server off the list when you retire it.

Check from each server that it reaches the others (this whitelists nothing):

```bash
curl -s -X POST -w ' HTTP %{http_code}\n' http://10.0.0.12:81/connecter.php
```

- `bad request ... HTTP 400`: works.
- `not allowed ... HTTP 403`: the calling server is not in that server's list. The PHP error log
  on that server shows the address it saw (`connecter.php refused a call from ...`).
- `HTTP 000`: not reachable (firewall, wrong address or port).

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
| `cluster_servers` | empty | All cluster servers, same list on every server (empty = single server) |
| `cluster_port` | 81 | Port `connecter.php` is called on |

## Where to look when something fails

- Web side: the PHP error log, lines starting with `lockdown:`.
- Firewall side: `journalctl -t lockdown`.
- Current list: `cat /var/lib/lockdown/allowed.list` (IP, expiry as a Unix time or 0 for no expiry, user).
