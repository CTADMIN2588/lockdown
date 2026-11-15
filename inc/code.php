<?php
// Login handler for access.php. Checks the VICIdial login, then whitelists the caller's IP.
// Always answers with a redirect back to access.php; that page shows the result.

require __DIR__ . '/lib.php';
ld_session_start();
ld_security_headers();

function ld_back($flash)
{
    if ($flash !== '') {
        $_SESSION['flash'] = $flash;
    }
    header('Location: ../access.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ld_back('');
}

$ip = ld_client_ip();
if ($ip === '') {
    ld_back('noip');
}
if (!ld_csrf_ok(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
    ld_back('expired');
}

// Locked out: do not even look at the password.
$attempts = ld_attempts($ip);
if ($attempts['locked_for'] > 0) {
    ld_back('');
}

$user = isset($_POST['user']) && is_string($_POST['user']) ? trim($_POST['user']) : '';
$pass = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

$login = ld_check_login($user, $pass);
if ($login === null) {
    ld_back('unavailable');
}
if ($login === false) {
    ld_attempt_fail($ip);
    ld_log('failed login from ' . $ip . ' as "' . preg_replace('/[^A-Za-z0-9_-]/', '?', substr($user, 0, 20)) . '"');
    ld_back('bad_login');
}

// Correct login.
ld_attempt_clear($ip);
session_regenerate_id(true);

if (!ld_allow_ip($ip, $login)) {
    ld_back('firewall');
}
$unreached = ld_notify_peers($ip, $login);
ld_log('allowed ' . $ip . ' for ' . $login . ($unreached ? ' (' . $unreached . ' cluster server(s) not reached)' : ''));

$state = ld_allow_state($ip);
$_SESSION['granted'] = array(
    'user'    => $login,
    'expires' => $state['expires'],
    'at'      => time(),
);
ld_back('');
