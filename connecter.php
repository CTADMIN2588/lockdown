<?php
$system_ip = $_GET['ip_address'];
echo $system_ip;
$command = "sudo firewall-cmd --zone=trusted --add-source=$system_ip --permanent 2>&1";
$exec_cmd = shell_exec($command);
$command2 = "sudo firewall-cmd --reload";
$exec_cmd2 = shell_exec($command2);
print_r("<pre>$exec_cmd</pre>");
?>
