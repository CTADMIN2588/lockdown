<?php

$server = 'localhost';
$user = 'root';
$password = '';
$database ='asterisk';

$asterisk_conn = mysqli_connect($server,$user,$password,$database);

if(!$asterisk_conn){
  echo 'Not Connected' or ide();
}
