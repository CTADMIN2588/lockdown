<?php
session_start();
include 'dbconnect.php';
if (isset($_POST['submit'])) {
  $user = $_POST['user'];
  $password = $_POST['password'];

  $login_query = mysqli_query($asterisk_conn, "SELECT * FROM vicidial_users WHERE user = '$user' AND pass = '$password'");
  $rowexists = mysqli_num_rows($login_query);
  if ($rowexists > 0) {
    foreach ($login_query as $row) {
      $user_name = $row['user'];
      $user_pass = $row['pass'];
      $user_group = $row['user_group'];

      $_SESSION['auth'] = true;
      $_SESSION['auth_user'] = [
        'user' => $user_name,
        'pass' => $user_pass,
        'user_group' => $user_group,
      ];

      $hostname = gethostname();
      $system_ip = $_SERVER['REMOTE_ADDR'];

      exec("sudo firewall-cmd --zone=trusted --add-source=$system_ip 2>&1", $exec_cmd, $value);

      $url = "http://$hostname:81/connecter.php?ip_address=$system_ip";
      $ch = curl_init($url);
      $response = curl_exec($ch);
      curl_close($ch);

      if ($value == 0) {
        header("Location: https://$hostname/vicidial/welcome.php");
      } else {
        $_SESSION['status'] = "Invalid Credentials.";
        header('Location: ../firewall.php');
      }
    }
  } else {
    $_SESSION['status'] = "Invalid Credentials.";
    header('Location: ../firewall.php');
  }
} else {
  $_SESSION['status'] = "Invalid Credentials.";
  header('Location: ../firewall.php');
}
