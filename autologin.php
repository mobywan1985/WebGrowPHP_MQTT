<?php
    if(isset($cookie_name))
    {
            // Check if the cookie exists
        if(isSet($_COOKIE[$cookie_name]))
                {
                parse_str($_COOKIE[$cookie_name]);
                // Make a verification
                $server_digest = hash_hmac('md5', gethostname().$usr, 'Mairzy doats and dozy doats and liddle lamzy divey');
                if($digest == $server_digest)
                        {
                        // Register the session
                        $_SESSION['username'] = $usr;
                        $_SESSION['cookie_name'] = $cookie_name;
                        }
                }
    }



?>