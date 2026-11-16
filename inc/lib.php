<?php
// Shared helpers for access.php, inc/code.php and connecter.php.
// Nothing here prints output.

function ld_config()
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}

function ld_log($message)
{
    error_log('lockdown: ' . $message);
}

function ld_e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// ---------------------------------------------------------------- session / headers

function ld_session_start()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('LOCKDOWNSESS');
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ));
    } else {
        session_set_cookie_params(0, '/; samesite=Strict', '', $secure, true);
    }
    session_start();
}

function ld_security_headers()
{
    // Everything the page needs is served from this folder, so nothing else is allowed.
    header("Content-Security-Policy: default-src 'none'; style-src 'self'; script-src 'self'; font-src 'self'; img-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');
}

function ld_csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function ld_csrf_ok($token)
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// ---------------------------------------------------------------- IP address

function ld_valid_ip($ip)
{
    return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

// The address the web server sees. Deliberately not X-Forwarded-For: a visitor can fake that header.
function ld_client_ip()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    if (stripos($ip, '::ffff:') === 0) {
        $ip = substr($ip, 7); // IPv4 seen through an IPv6 socket
    }
    return ld_valid_ip($ip) ? $ip : '';
}

// ---------------------------------------------------------------- database / login

require __DIR__ . '/dbconnect.php';   // ld_db()

// VICIdial's own hash of a password (used when pass_hash_enabled = 1). Empty string on failure.
function ld_bp_hash($pass)
{
    $config = ld_config();
    if (!is_file($config['bp_script'])) {
        ld_log('pass_hash_enabled is on but ' . $config['bp_script'] . ' was not found');
        return '';
    }
    $out = exec(escapeshellarg($config['bp_script']) . ' --pass=' . escapeshellarg($pass));
    return preg_replace('/PHASH: |\s/', '', (string) $out);
}

// Returns the user name on success, false on a wrong login, null when the check could not be done.
function ld_check_login($user, $pass)
{
    if (!preg_match('/^[A-Za-z0-9_-]{2,20}$/', $user) || $pass === '' || strlen($pass) > 100) {
        return false;
    }
    $db = ld_db();
    if (!$db) {
        return null;
    }
    $config = ld_config();

    $hash_on = false;
    $res = mysqli_query($db, 'SELECT pass_hash_enabled FROM system_settings LIMIT 1');
    if ($res && ($row = mysqli_fetch_row($res))) {
        $hash_on = ((int) $row[0] > 0);
    }

    $column = $hash_on ? 'pass_hash' : 'pass';
    $stmt = mysqli_prepare($db, "SELECT user, $column, user_level, active FROM vicidial_users WHERE user = ? LIMIT 1");
    if (!$stmt) {
        ld_log('login query failed: ' . mysqli_error($db));
        mysqli_close($db);
        return null;
    }
    mysqli_stmt_bind_param($stmt, 's', $user);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $db_user, $db_secret, $db_level, $db_active);
    $found = mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($db);

    if (!$found || (string) $db_secret === '') {
        return false;
    }
    if ($hash_on) {
        $given = ld_bp_hash($pass);
        if ($given === '') {
            return null;
        }
    } else {
        $given = $pass;
    }
    if (!hash_equals((string) $db_secret, $given)) {
        return false;
    }
    if ($db_active !== 'Y' || (int) $db_level < (int) $config['min_user_level']) {
        return false;
    }
    return (string) $db_user;
}

// ---------------------------------------------------------------- failed-login lockout

function ld_attempt_file($ip)
{
    $config = ld_config();
    return $config['data_dir'] . '/attempts/' . sha1($ip) . '.json';
}

// array('count' => failed tries so far, 'locked_for' => seconds left on a lockout)
function ld_attempts($ip)
{
    $config = ld_config();
    $window = (int) $config['lock_minutes'] * 60;
    $now = time();
    $data = @json_decode((string) @file_get_contents(ld_attempt_file($ip)), true);
    if (!is_array($data)) {
        return array('count' => 0, 'locked_for' => 0);
    }
    $locked_until = isset($data['locked_until']) ? (int) $data['locked_until'] : 0;
    $last = isset($data['last']) ? (int) $data['last'] : 0;
    if ($locked_until > $now) {
        return array('count' => (int) $config['max_tries'], 'locked_for' => $locked_until - $now);
    }
    if ($locked_until > 0 || $now - $last > $window) {
        return array('count' => 0, 'locked_for' => 0); // lockout served, or old tries forgotten
    }
    return array('count' => isset($data['count']) ? (int) $data['count'] : 0, 'locked_for' => 0);
}

function ld_attempt_fail($ip)
{
    $config = ld_config();
    $state = ld_attempts($ip);
    $count = $state['count'] + 1;
    $now = time();
    $data = array('count' => $count, 'last' => $now, 'locked_until' => 0);
    if ($count >= (int) $config['max_tries']) {
        $data['locked_until'] = $now + (int) $config['lock_minutes'] * 60;
    }
    if (@file_put_contents(ld_attempt_file($ip), json_encode($data), LOCK_EX) === false) {
        ld_log('cannot write the failed-login counter in ' . $config['data_dir'] . '/attempts (lockout is NOT working)');
    }
}

function ld_attempt_clear($ip)
{
    @unlink(ld_attempt_file($ip));
}

// ---------------------------------------------------------------- firewall allow list

// array('allowed' => bool, 'expires' => unix time, or 0 when the IP has no expiry (added by an admin))
function ld_allow_state($ip)
{
    $config = ld_config();
    $none = array('allowed' => false, 'expires' => 0);
    $lines = @file($config['data_dir'] . '/allowed.list', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $none;
    }
    foreach ($lines as $line) {
        $parts = explode(' ', trim($line));
        if (count($parts) >= 2 && $parts[0] === $ip) {
            $expires = (int) $parts[1];
            if ($expires === 0 || $expires > time()) {
                return array('allowed' => true, 'expires' => $expires);
            }
            return $none;
        }
    }
    return $none;
}

// Runs firewall-cmd as root through sudo, like the old version did. Every argument is escaped.
// Returns the exit code; $out receives the output lines.
function ld_fw($args, &$out = null)
{
    $cmd = 'sudo -n /usr/bin/firewall-cmd';
    foreach ($args as $arg) {
        $cmd .= ' ' . escapeshellarg($arg);
    }
    $out = array();
    $code = 1;
    exec($cmd . ' 2>&1', $out, $code);
    return (int) $code;
}

// Adds the IP to the firewalld "trusted" zone, now and after a reboot, and records when it
// expires (scripts/expire.sh removes it then). An IP an admin already put in the zone by hand
// is recorded with no expiry, so it is never removed. True on success.
function ld_allow_ip($ip, $user)
{
    $config = ld_config();
    // One single address only: filter_var refuses ranges, so 0.0.0.0/0 can never get in.
    if (!ld_valid_ip($ip) || !preg_match('/^[A-Za-z0-9_-]{1,20}$/', $user)) {
        return false;
    }
    $list = $config['data_dir'] . '/allowed.list';
    $lock = @fopen($config['data_dir'] . '/.allowed.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX)) {
        ld_log('cannot lock ' . $config['data_dir'] . '/.allowed.lock: missing or not owned by the web server');
        return false;
    }

    // What the allow list already says about this IP: null = not listed, 0 = no expiry.
    $keep = array();
    $listed = null;
    $lines = @file($list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines ? $lines : array() as $line) {
        $parts = explode(' ', trim($line));
        if ($parts[0] === $ip) {
            $listed = isset($parts[1]) ? (int) $parts[1] : 0;
        } else {
            $keep[] = trim($line);
        }
    }

    $out = array();
    $permanent = (ld_fw(array('--permanent', '--zone=trusted', '--query-source=' . $ip)) === 0);
    $ok = ld_fw(array('--zone=trusted', '--query-source=' . $ip)) === 0
       || ld_fw(array('--zone=trusted', '--add-source=' . $ip), $out) === 0;
    if ($ok && !$permanent) {
        $ok = ld_fw(array('--permanent', '--zone=trusted', '--add-source=' . $ip), $out) === 0;
    }
    if (!$ok) {
        ld_log('firewall-cmd failed for ' . $ip . ': ' . implode(' | ', $out));
        flock($lock, LOCK_UN);
        fclose($lock);
        return false;
    }

    $expires = time() + (int) $config['allow_days'] * 86400;
    if ($listed === 0 || ($listed === null && $permanent)) {
        $expires = 0;   // put there by an admin: never expires
    }
    $keep[] = $ip . ' ' . $expires . ' ' . $user;
    if (@file_put_contents($list, implode("\n", $keep) . "\n") === false) {
        ld_log('cannot write ' . $list . ' (not writable by the web server); ' . $ip . ' is allowed but will not expire');
    }
    flock($lock, LOCK_UN);
    fclose($lock);
    return true;
}

// ---------------------------------------------------------------- cluster sync

// The servers in 'cluster_servers' as array('host' => ..., 'url' => ...). An entry is an IP address
// or a host name; a full http(s):// URL is also accepted for a server not on cluster_port.
function ld_cluster_servers()
{
    $config = ld_config();
    $list = (isset($config['cluster_servers']) && is_array($config['cluster_servers'])) ? $config['cluster_servers'] : array();
    $port = isset($config['cluster_port']) ? (int) $config['cluster_port'] : 81;
    $servers = array();
    foreach ($list as $entry) {
        $entry = trim((string) $entry);
        if (preg_match('#^https?://#i', $entry)) {
            $host = trim((string) parse_url($entry, PHP_URL_HOST), '[]');
            $url = $entry;
        } else {
            $host = $entry;
            $url = 'http://' . (strpos($host, ':') !== false ? '[' . $host . ']' : $host) . ':' . $port . '/connecter.php';
        }
        if ($host === '' || (!ld_valid_ip($host) && !preg_match('/^[A-Za-z0-9.-]{1,253}$/', $host))) {
            ld_log('cluster_servers: ignoring "' . preg_replace('/[^\x21-\x7e]/', '?', $entry) . '"');
            continue;
        }
        $servers[] = array('host' => $host, 'url' => $url);
    }
    return $servers;
}

// The IP addresses of a cluster_servers entry. A host name is looked up in DNS / /etc/hosts.
function ld_host_ips($host)
{
    if (ld_valid_ip($host)) {
        return array($host);
    }
    $ips = @gethostbynamel($host);
    return is_array($ips) ? $ips : array();
}

// True when a cluster_servers entry is this server, so it is not called a second time.
function ld_is_this_server($host)
{
    $mine = array('127.0.0.1', '::1');
    if (!empty($_SERVER['SERVER_ADDR'])) {
        $mine[] = preg_replace('/^::ffff:/i', '', (string) $_SERVER['SERVER_ADDR']);
    }
    $name = gethostname();
    if (is_string($name) && $name !== '') {
        if (strcasecmp($name, $host) === 0) {
            return true;
        }
        $mine = array_merge($mine, ld_host_ips($name));
    }
    return count(array_intersect(ld_host_ips($host), $mine)) > 0;
}

// True when a call to connecter.php comes from a server in cluster_servers (or from this server).
function ld_is_cluster_caller($ip)
{
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return true;
    }
    foreach (ld_cluster_servers() as $server) {
        if (in_array($ip, ld_host_ips($server['host']), true)) {
            return true;
        }
    }
    return false;
}

// Tells every other server in cluster_servers to allow the IP too: one call per server, like the
// old code.php. Returns how many servers could not be reached (the login still succeeds here).
function ld_notify_peers($ip, $user)
{
    $failed = 0;
    foreach (ld_cluster_servers() as $server) {
        if (ld_is_this_server($server['host'])) {
            continue;
        }
        $ch = curl_init($server['url']);
        curl_setopt_array($ch, array(
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query(array('ip' => $ip, 'user' => $user)),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => 6,
        ));
        curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status !== 200) {
            $failed++;
            ld_log('cluster sync to ' . $server['url'] . ' failed (HTTP ' . $status . ' ' . curl_error($ch) . ') for ' . $ip);
        }
        curl_close($ch);
    }
    return $failed;
}

function ld_redirect_url()
{
    $config = ld_config();
    if ($config['redirect_url'] !== '') {
        return $config['redirect_url'];
    }
    return 'https://' . gethostname() . '/vicidial/welcome.php';
}
