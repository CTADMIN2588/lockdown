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
    // Every server in the cluster, this one included. Put the SAME list on every server.
    // After a login on any server, each server in the list is called at
    // http://<server>:<cluster_port>/connecter.php and allows the same IP
    // (what the old code.php did with one curl call per server).
    // connecter.php only accepts calls that come from a server in this list.
    // Use the addresses the servers reach each other on. Leave empty on a single server.
    // Example: array('10.0.0.11', '10.0.0.12', '10.0.0.13')
    'cluster_servers' => array(),

    // Port the lockdown folder is served on (the port users open access.php on).
    'cluster_port'   => 81,
);
