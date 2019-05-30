<?php

require_once 'config.php';
require_once '/home/pi/vendor/autoload.php';
$loader = new Twig_Loader_Filesystem('dashboard/templates');
$twig = new Twig_Environment($loader);
// Is the user already logged in? Redirect him/her to the private page
if(isset($_SESSION['username']))
{
header("Location: dashboard/");
exit;
}


if(isset($_POST['submit']))
{
$do_login = true;
include_once 'do_login.php';
}
$error = array();
if($login_error)
{
//echo '<div id="error_notification">The submitted login info is incorrect.</div>';
  $error[] = 'Username or Password incorrect!';
}
echo $twig->render('login.html', ['error' => $error]);
?>