<?php
// Lockdown settings.

return array(
    // ---- Cluster ----------------------------------------------------------------------------
    // Every server in the cluster, this one included, one per line (host name or IP).
    // Use the SAME list on every server. After a sign-in on any server, each server here is
    // called at http://<server>:81/connecter.php and allows the same IP, like the curl lines
    // in the old code.php. connecter.php only accepts calls from these servers.
    // Leave the list empty on a single server.
    'cluster_servers' => array(
        // 'db-splendid',
        // 'web-splendid',
    ),

    // Port the lockdown folder is served on (the port users open access.php on).
    'cluster_port'   => 81,

    // ---- Access -----------------------------------------------------------------------------
    'allow_days'     => 7,     // days an IP stays allowed; signing in again renews it
    'max_tries'      => 5,     // wrong logins from one IP before it is locked out ...
    'lock_minutes'   => 15,    // ... for this long
    'min_user_level' => 1,     // lowest vicidial_users.user_level that may sign in (1 = all active users)

    // Where to go after access is granted. Empty = https://<this server>/vicidial/welcome.php
    'redirect_url'   => '',

    // ---- Files (normally nothing to change) -------------------------------------------------
    'conf_file'      => '/etc/astguiclient.conf',      // VICIdial's config: database login
    'bp_script'      => '/var/www/html/agc/bp.pl',     // VICIdial's password hashing (only if hashing is on)
    'data_dir'       => '/var/lib/lockdown',           // allow list and failed-login counters (made by setup.sh)
);
