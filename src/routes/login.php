<?php
use Krizalys\Onedrive\Onedrive;

function generate(): string
{
    session_start(["cookie_samesite" => "lax"]);

    $config = require($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    $client = Onedrive::client($config['ONEDRIVE_CLIENT_ID']);
    $url = $client->getLogInUrl([
        'files.read',
        'files.read.all',
        'files.readwrite',
        'files.readwrite.all',
        'offline_access',
    ], $config['ONEDRIVE_REDIRECT_URI']);

    // Persist the OneDrive client' state for next API requests.
    $_SESSION['onedrive.client.state'] = $client->getState();

    // redirect to login URL
    header('HTTP/1.1 302 Found', true, 302);
    header("Location: $url");

    return "logging in...";
}
