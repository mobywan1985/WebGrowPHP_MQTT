<?php

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'web');
define('DB_PASSWORD', 'webgrow1985');
define('DB_DATABASE', 'webgrow');
$db = mysqli_connect(DB_SERVER,DB_USERNAME,DB_PASSWORD,DB_DATABASE);


error_reporting(E_ALL ^ E_NOTICE);

session_start(); // Start Session
header('Cache-control: private'); // IE 6 FIX

// always modified
header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
// HTTP/1.1
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
// HTTP/1.0
header('Pragma: no-cache');

// ---------- Login Info ---------- //

if($_SERVER["REQUEST_METHOD"] == "POST") {
      // username and password sent from form 

      $myusername = mysqli_real_escape_string($db,$_POST['username']);
      $mypassword = mysqli_real_escape_string($db,$_POST['password']);

      $sql = "SELECT username, password FROM user WHERE username = '$myusername'";
      $result = mysqli_query($db,$sql);
      $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
      $active = $row['active'];
      $count = mysqli_num_rows($result);
      $config_username = $row['username'];
      $config_password = $row['password'];
}

// ---------- Cookie Info ---------- //

$cookie_name = 'siteAuth';
$cookie_time = (3600 * 24 * 30); // 30 days

if(!isset($_SESSION['username']))
{
  include_once 'autologin.php';
}

?>