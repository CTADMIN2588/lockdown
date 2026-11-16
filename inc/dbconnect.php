<?php
// Database connection for the login check (the same job as the old dbconnect.php).
//
// Nothing to change on a normal VICIdial server: the database address, user and password are
// read from /etc/astguiclient.conf, the file VICIdial itself uses. On an Asterisk server that
// file already points to the database server. No password is stored in this folder.
//
// Only if this server must use a different database server than VICIdial does, put its IP here:
define('LD_DB_HOST', '');

function ld_db()
{
    $config = ld_config();
    $lines = @file($config['conf_file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        ld_log('cannot read ' . $config['conf_file']);
        return null;
    }
    $conf = array();
    foreach ($lines as $line) {
        if (preg_match('/^(VARDB_\w+)\s*=>\s*(.*)$/', trim($line), $m)) {
            $conf[$m[1]] = trim($m[2]);
        }
    }
    foreach (array('VARDB_server', 'VARDB_database', 'VARDB_user', 'VARDB_pass') as $key) {
        if (!isset($conf[$key])) {
            ld_log('missing ' . $key . ' in ' . $config['conf_file']);
            return null;
        }
    }
    $host = LD_DB_HOST !== '' ? LD_DB_HOST : $conf['VARDB_server'];
    $port = isset($conf['VARDB_port']) ? (int) $conf['VARDB_port'] : 3306;
    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @mysqli_connect($host, $conf['VARDB_user'], $conf['VARDB_pass'], $conf['VARDB_database'], $port);
    if (!$db) {
        ld_log('database connection to ' . $host . ' failed: ' . mysqli_connect_error());
        return null;
    }
    mysqli_set_charset($db, 'utf8');
    return $db;
}
