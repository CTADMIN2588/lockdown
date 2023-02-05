<?php
$system_ip = $_GET['ip_address'];
echo $system_ip;
$command = "sudo firewall-cmd --zone=trusted --add-source=$system_ip 2>&1";
$exec_cmd = shell_exec($command);
print_r("<pre>$exec_cmd</pre>");
?>