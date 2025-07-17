<?php
// Initialize a new OneDrive client.
function init_onedrive_client(): array
{
    $config = require($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    $client = Krizalys\Onedrive\Onedrive::client($config['ONEDRIVE_CLIENT_ID']);
    $login_url = $client->getLogInUrl([
        'files.read',
        'files.read.all',
        'files.readwrite',
        'files.readwrite.all',
        'offline_access',
    ], $config['ONEDRIVE_REDIRECT_URI']);

    return [$client, $login_url];
}

// Obtain the access token using the code received by the OneDrive API.
function obtain_onedrive_access_token(Krizalys\Onedrive\Client $client, string $code): void
{
    $config = require($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    $client->obtainAccessToken($config['ONEDRIVE_CLIENT_SECRET'], $_GET['code']);
}

// Persist OneDrive client state in the session for next API requests.
function save_onedrive_client_state_to_session(Krizalys\Onedrive\Client $client): void
{
    assert(session_id());

    $_SESSION['onedrive.client.state'] = $client->getState();
}

// Restore OneDrive client state from the session or return null if not logged-in.
function restore_onedrive_client_from_session(): ?Krizalys\Onedrive\Client
{
    assert(session_id());

    $config = require($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    if (!array_key_exists('onedrive.client.state', $_SESSION))
        return null;  // not logged-in -> should redirect to /login

    // restore the previous state while instantiating this client to proceed in obtaining an access token
    return Krizalys\Onedrive\Onedrive::client($config['ONEDRIVE_CLIENT_ID'], ['state' => $_SESSION['onedrive.client.state']]);
}

function get_drive_item(Krizalys\Onedrive\Client $client, string $path): Krizalys\Onedrive\Proxy\DriveItemProxy
{
    if ($path === '/')
        return $client->getRoot();
    else
        return $client->getDriveItemByPath($path);
}

function get_drive_item_type(Krizalys\Onedrive\Proxy\DriveItemProxy $item): string
{
    return $item->folder ? "folder" : "file";
}

function build_drive_item_url(Krizalys\Onedrive\Proxy\DriveItemProxy $item, string $path): string
{
    $type = get_drive_item_type($item);
    $prefix = $type === "file" ? "download" : "drive";
    $slash = $path === '/' ? '' : '/';
    return "/{$prefix}{$path}{$slash}{$item->name}";
}
