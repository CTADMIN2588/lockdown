<?php
// Cluster sync endpoint. Another cluster server calls this after a successful login there,
// so the same IP is whitelisted here too. It is not for browsers:
//   - POST only
//   - the caller's address must be in 'peer_ips' (inc/config.php)
//   - the request must be signed with the shared key (key_file), and be less than 2 minutes old
//   - the IP must be one single valid address

require __DIR__ . '/inc/lib.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function ld_reply($status, $message)
{
    http_response_code($status);
    echo json_encode(array('ok' => $status === 200, 'message' => $message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ld_reply(405, 'POST only');
}

$config = ld_config();
$caller = ld_client_ip();
if ($caller === '' || !in_array($caller, $config['peer_ips'], true)) {
    ld_log('connecter.php refused a call from ' . ($caller === '' ? 'an unknown address' : $caller));
    ld_reply(403, 'not allowed');
}

$key = ld_key();
if ($key === '') {
    ld_reply(503, 'shared key not set up');
}

$ip   = isset($_POST['ip'])   && is_string($_POST['ip'])   ? $_POST['ip']   : '';
$user = isset($_POST['user']) && is_string($_POST['user']) ? $_POST['user'] : '';
$ts   = isset($_POST['ts'])   && is_string($_POST['ts'])   ? $_POST['ts']   : '';
$sig  = isset($_POST['sig'])  && is_string($_POST['sig'])  ? $_POST['sig']  : '';

if (!ld_valid_ip($ip) || !preg_match('/^[A-Za-z0-9_-]{1,20}$/', $user) || !ctype_digit($ts)) {
    ld_reply(400, 'bad request');
}
if (abs(time() - (int) $ts) > 120) {
    ld_reply(403, 'request too old');
}
if (!hash_equals(ld_sign($ts, $ip, $user, $key), $sig)) {
    ld_log('connecter.php got a bad signature from ' . $caller);
    ld_reply(403, 'bad signature');
}

if (!ld_allow_ip($ip, $user)) {
    ld_reply(500, 'firewall not updated');
}
ld_log('allowed ' . $ip . ' for ' . $user . ' (synced from ' . $caller . ')');
ld_reply(200, 'allowed');
