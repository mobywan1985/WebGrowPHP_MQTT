<?php

$cookie_name = $_SESSION['cookie_name'];
$_SESSION = array();
session_destroy();
$_GET = array();
unset($_COOKIE[$cookie_name]);
// empty value and expiration one hour before
$res = setcookie($cookie_name, '', time() - 3600, '/');
header("Location: ../");
exit;

?>