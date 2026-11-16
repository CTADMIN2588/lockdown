<?php
// Lockdown settings. Everything that differs between servers lives here.
// No passwords in this file: the database login is read from astguiclient.conf.

return array(
    // How long a whitelisted IP stays in the firewall.
    'allow_days'     => 7,

    // Wrong logins allowed from one IP before it is locked out, and for how long.
    'max_tries'      => 5,
    'lock_minutes'   => 15,

    // Lowest vicidial_users.user_level that may open the firewall (1 = every active user).
    'min_user_level' => 1,

    // Where to send the user after access is granted.
    // Empty = https://<this server's hostname>/vicidial/welcome.php (the old behaviour).
    'redirect_url'   => '',

    // VICIdial's own config file; the database login is read from it.
    'conf_file'      => '/etc/astguiclient.conf',

    // VICIdial's password hashing script, used only when system_settings.pass_hash_enabled = 1.
    'bp_script'      => '/var/www/html/agc/bp.pl',

    // Folder for the allow list and the failed-login counters (see INSTALL.md).
    'data_dir'       => '/var/lib/lockdown',

    // Root-owned helper that changes the firewall. The only command Apache may run with sudo.
    'allow_cmd'      => '/usr/local/sbin/lockdown-allow',

    // ---- Cluster sync -------------------------------------------------------
    // Shared secret for server-to-server calls. Same file content on every server.
    'key_file'       => '/etc/lockdown.key',

    // connecter.php on the OTHER servers. After a login here, each one is told to allow the IP.
    // Example: array('http://10.0.0.12:81/connecter.php', 'http://10.0.0.13:81/connecter.php')
    'peers'          => array(),

    // Addresses allowed to call connecter.php on THIS server (the other cluster servers).
    'peer_ips'       => array('127.0.0.1', '::1'),
);
