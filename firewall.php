<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Validation</title>
  <link rel="stylesheet" href="css/login.css">
</head>
<body>
  <div class="center">
    <h1>Firewall Login</h1>
    <form action="inc/code.php" method="post">
      <div class="txt_field">
        <input type="text" id="user" name="user" required>
        <span></span>
        <label>Username</label>
      </div>

      <div class="txt_field">
        <input type="hidden" id="password" name="password" />
        <input type="password" id="password" name="password" required>
        <span></span>
        <label>Password</label>
      </div>
      <div class="pass"></div>
      <input type="submit" value="Login" name="submit">
      <br>
      <?php
      if(isset($_SESSION['status'])){
        ?>
        <h4 style="color:red;"><center><? echo $_SESSION['status']; ?></center></h4>
      <?php
      unset($_SESSION['status']);
      }
      ?>
      <div class="signup_link">
      </div>
    </form>
  </div>
</body>
</html>
