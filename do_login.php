<?php
if(!$do_login) exit;

// declare post fields

$post_autologin = $_POST['autologin'];


if(password_verify($_POST['password'], $config_password))
{
$login_ok = true;

$_SESSION['username'] = $config_username;
$_SESSION['cookie_name'] = $cookie_name;
// Autologin Requested?

if($post_autologin == 1)
        {
        $password_hash = $config_password; // will result in a 32 characters hash
        $digest = hash_hmac('md5', gethostname().$_POST['username'], 'Mairzy doats and dozy doats and liddle lamzy divey');
        setcookie($cookie_name, 'usr='.$config_username.'&digest='.$digest, time() + $cookie_time, '/');
        }

header("Location: dashboard/");
exit;
}
else
{
$login_error = true;
}
?>