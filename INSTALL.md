# Vicidial Secure Access (lockdown): install

A user opens `http://<server>:81/access.php`, signs in with their VICIdial login, and their IP address
is added to the firewalld `trusted` zone for 7 days. After 7 days it is removed automatically and
they sign in again. In a cluster, one sign-in on any server whitelists the IP on every server.

Run every command as root. The examples use the servers `db-splendid` and `web-splendid`;
use your own server names.

- **Part A**: install on each server (same steps on every server).
- **Part B**: connect the servers into a cluster (skip on a single server).
- **Part C**: clean up IPs left by the old version.

## What is in this folder

| File | What it does |
|---|---|
| `access.php` | The page users open |
| `inc/code.php` | Checks the login, whitelists the IP, tells the other cluster servers |
| `inc/lib.php`, `inc/config.php` | Shared code and settings |
| `connecter.php` | Cluster sync: another server tells this one to allow an IP |
| `css/access.css`, `js/access.js`, `fonts/` | Page styling, script, fonts (all local) |
| `scripts/lockdown-allow` | Root helper that changes the firewall |
| `scripts/lockdown-expire` | Root helper, run hourly, removes expired IPs |

---

## Part A: install on each server

Do Part A on one server, test it, then the next server.

### A1. Move the old version out of the way

A fresh folder avoids leftovers (the old `inc/dbconnect.php` holds a database password).

```bash
[ -d /var/www/lockdown ] && mv /var/www/lockdown /root/lockdown_old_$(date +%F)
```

### A2. Download the new version

```bash
cd /var/www && curl -sL https://github.com/CTADMIN2588/lockdown/archive/refs/heads/main.tar.gz | tar xz && mv lockdown-main lockdown && ls /var/www/lockdown
```

### A3. Check the PHP

Every file should print `No syntax errors detected`.

```bash
for f in /var/www/lockdown/access.php /var/www/lockdown/connecter.php /var/www/lockdown/inc/*.php; do php -l "$f"; done
```

### A4. Root helpers

```bash
install -o root -g root -m 755 /var/www/lockdown/scripts/lockdown-allow /usr/local/sbin/lockdown-allow && install -o root -g root -m 755 /var/www/lockdown/scripts/lockdown-expire /usr/local/sbin/lockdown-expire
```

### A5. Data folder

`allowed.list` is written by root and read by the web server. `attempts/` is written by the web server.

```bash
mkdir -p /var/lib/lockdown/attempts && chown root:root /var/lib/lockdown && chmod 755 /var/lib/lockdown && chown apache:apache /var/lib/lockdown/attempts && chmod 700 /var/lib/lockdown/attempts
```

Only if `getenforce` prints `Enforcing` (SELinux on):

```bash
semanage fcontext -a -t httpd_sys_rw_content_t "/var/lib/lockdown/attempts(/.*)?" && restorecon -R /var/lib/lockdown
```

### A6. Hourly expiry

```bash
echo '7 * * * * root /usr/local/sbin/lockdown-expire' > /etc/cron.d/lockdown
```

### A7. sudo for the web server

The web server may run one command as root: the helper. Check:

```bash
sudo -u apache sudo -n -l
```

If neither `/usr/local/sbin/lockdown-allow` nor `ALL` is listed, add the rule:

```bash
echo 'apache ALL=(root) NOPASSWD: /usr/local/sbin/lockdown-allow' > /etc/sudoers.d/lockdown && chmod 440 /etc/sudoers.d/lockdown && visudo -cf /etc/sudoers.d/lockdown
```

If PHP does not run as `apache` (`ps -eo user,comm | grep php-fpm`), use that user name here and in A5.

A broad rule such as `apache ALL=(ALL) NOPASSWD: ALL` lets every PHP page run anything as root.
Lockdown only needs the line above; replace the broad rule when you can.

### A8. Port 81

Users open the page on port 81, as in the old version. It should print `200`:

```bash
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:81/access.php
```

If it prints `000` or `404`, check whether a port 81 setup already exists before adding one
(a second `Listen 81` stops Apache from starting):

```bash
grep -rn "81" /etc/httpd/conf/httpd.conf /etc/httpd/conf.d/ | grep -iE "listen|virtualhost"
```

### A9. Database (Asterisk servers)

Nothing to edit: the database login is read from `/etc/astguiclient.conf`, the same file VICIdial
uses. On an Asterisk server it already points to the database server. Check (prints no password):

```bash
grep VARDB_server /etc/astguiclient.conf
```

Lockdown only reads `vicidial_users` and `system_settings`; it never writes to the database.

### A10. Test this server

Open `http://<this server>:81/access.php`, sign in, then:

```bash
cat /var/lib/lockdown/allowed.list; firewall-cmd --zone=trusted --list-sources
```

Both should show your IP. If the page says the firewall could not be updated:

```bash
grep "lockdown:" /var/log/php-fpm/www-error.log | tail -5
```

The server now works on its own.

---

## Part B: connect the servers into a cluster

Do this once every server has finished Part A. After a sign-in on any server, that server calls
`http://<server>:81/connecter.php` on every other server in the list (like the old `code.php`
did with one `curl` call per server), and each of them whitelists the same IP.

### B1. Server names resolve everywhere

On each server, every name should print an IP:

```bash
getent hosts db-splendid web-splendid
```

If one prints nothing, add it to `/etc/hosts` on that server, or use IP addresses in the list.

### B2. The same server list on every server

List every server, that server included. Use exactly the same line on every server:

```bash
sed -i "s|'cluster_servers' => array(),|'cluster_servers' => array('db-splendid', 'web-splendid'),|" /var/www/lockdown/inc/config.php && grep cluster_servers /var/www/lockdown/inc/config.php && php -l /var/www/lockdown/inc/config.php
```

`connecter.php` only accepts calls from a server in this list. Take a server off the list when you retire it.

### B3. Test the connections (whitelists nothing)

From each server, call each of the others:

```bash
curl -s -X POST -w ' HTTP %{http_code}\n' http://web-splendid:81/connecter.php
```

| Reply | Meaning |
|---|---|
| `bad request ... HTTP 400` | Works |
| `not allowed ... HTTP 403` | The calling server is not in that server's list, or calls from another IP. That server's error log shows the IP it saw (`connecter.php refused a call from ...`). |
| `HTTP 000` | Not reachable: firewall, wrong name, or nothing on port 81 |

### B4. Test a real sign-in

Sign in on one server, then on every server:

```bash
cat /var/lib/lockdown/allowed.list
```

The IP should be on all of them. If one is missing, on the server where you signed in:

```bash
grep "cluster sync" /var/log/php-fpm/www-error.log | tail -5
```

### Adding a server later

1. Part A on the new server.
2. Add its name to `cluster_servers` on **every** server (B2 with the longer list).
3. B3 and B4 again.

Users who signed in before the new server joined are added to it the next time they sign in.

---

## Part C: IPs left by the old version (once per server)

`lockdown-expire` only removes IPs that the new version added. Anything already in the `trusted`
zone is treated as added by an admin and is **never removed**, so office lines and the cluster
servers themselves stay safe. That also means user IPs added by the old version stay until you
remove them once by hand:

```bash
firewall-cmd --permanent --zone=trusted --list-sources
```

For each old user IP (keep the cluster servers and office IPs):

```bash
firewall-cmd --permanent --zone=trusted --remove-source=<ip> && firewall-cmd --reload
```

---

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

When you update lockdown later, keep a copy of `inc/config.php` and put your `cluster_servers` line back.

## Where to look when something fails

- Web side: the PHP error log, lines starting with `lockdown:` (`/var/log/php-fpm/www-error.log`).
- Firewall side: `journalctl -t lockdown`.
- Current list: `cat /var/lib/lockdown/allowed.list` (IP, expiry as a Unix time or 0 for no expiry, user).
