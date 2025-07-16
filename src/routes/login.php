<?php
use Krizalys\Onedrive\Onedrive;

function handle_route(): RequestResult
{
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
    return RequestResult::redirect($url);
}
