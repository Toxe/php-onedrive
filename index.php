<?php
require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/src/router.php');
require(__DIR__ . '/src/template.php');

session_start(["cookie_samesite" => "lax"]);

echo use_template('main', ['content' => route(), 'header' => generate_header()]);


function generate_header(): string
{
    $logged_in = array_key_exists('onedrive.client.state', $_SESSION);
    return use_template('header', ['logged_in' => $logged_in]);
}
