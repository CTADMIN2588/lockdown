<?php
// Cluster sync endpoint. Another cluster server calls this after a successful login there,
// so the same IP is whitelisted here too. It is not for browsers:
//   - POST only
//   - the caller must be one of the servers in 'cluster_servers' (inc/config.php)
//   - the IP must be one single valid address (no ranges), checked again by the root helper

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

$caller = ld_client_ip();
if ($caller === '' || !ld_is_cluster_caller($caller)) {
    ld_log('connecter.php refused a call from ' . ($caller === '' ? 'an unknown address' : $caller) . ' (not in cluster_servers)');
    ld_reply(403, 'not allowed');
}

$ip   = isset($_POST['ip'])   && is_string($_POST['ip'])   ? $_POST['ip']   : '';
$user = isset($_POST['user']) && is_string($_POST['user']) ? $_POST['user'] : '';

if (!ld_valid_ip($ip) || !preg_match('/^[A-Za-z0-9_-]{1,20}$/', $user)) {
    ld_reply(400, 'bad request');
}

if (!ld_allow_ip($ip, $user)) {
    ld_reply(500, 'firewall not updated');
}
ld_log('allowed ' . $ip . ' for ' . $user . ' (synced from ' . $caller . ')');
ld_reply(200, 'allowed');
