<?php
enum FileType
{
    case Unknown;
    case File;
    case Folder;
}

// Initialize a new OneDrive client.
function init_onedrive_client(): array
{
    $config = load_config();

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
    $config = load_config();

    $client->obtainAccessToken($config['ONEDRIVE_CLIENT_SECRET'], $code);
}

function save_onedrive_user_info_to_session(Krizalys\Onedrive\Client $client): void
{
    assert(is_session_active());

    // save the name of the logged-in user in the session
    $_SESSION["my_name"] = $client->getMyDrive()->owner->user->displayName;
}

// Persist OneDrive client state in the session for next API requests.
function save_onedrive_client_state_to_session(Krizalys\Onedrive\Client $client): void
{
    assert(is_session_active());

    $_SESSION['onedrive.client.state'] = $client->getState();
}

// Restore OneDrive client state from the session or return null if not logged-in.
function restore_onedrive_client_from_session(): ?Krizalys\Onedrive\Client
{
    assert(is_session_active());

    if (!is_logged_in_to_onedrive())
        return null;

    $config = load_config();

    // restore the previous state while instantiating this client to proceed in obtaining an access token
    return Krizalys\Onedrive\Onedrive::client($config['ONEDRIVE_CLIENT_ID'], ['state' => $_SESSION['onedrive.client.state']]);
}

function is_logged_in_to_onedrive(): bool
{
    return array_key_exists('onedrive.client.state', $_SESSION);
}

function get_drive_item(Krizalys\Onedrive\Client $client, string $item_id): Krizalys\Onedrive\Proxy\DriveItemProxy
{
    $drive_id = get_drive_id_from_item_id($item_id);
    $drive = $client->getDriveById($drive_id);
    return $drive->getDriveItemById($item_id);
}

function get_drive_item_type(Krizalys\Onedrive\Proxy\DriveItemProxy|Krizalys\Onedrive\Proxy\RemoteItemProxy $item): FileType
{
    // Is this a remote/shared item?
    if (is_a($item, "Krizalys\Onedrive\Proxy\DriveItemProxy") && $item->remoteItem)
        return get_drive_item_type($item->remoteItem);

    if ($item->folder)
        return FileType::Folder;
    else if ($item->file)
        return FileType::File;
    else {
        error_log("get_drive_item_type: unknown type of drive item \"{$item->name}\".");
        return FileType::Unknown;
    }
}

function get_drive_id_from_item_id(string $item_id): ?string
{
    if (($pos = strpos($item_id, "!")) === false)
        return null;

    return substr($item_id, 0, $pos);
}

function build_drive_item_url(Krizalys\Onedrive\Proxy\DriveItemProxy $item): string
{
    if (get_drive_item_type($item) === FileType::File) {
        $name = urlencode($item->name);
        return "/download/{$item->id}/{$name}";
    } else {
        return "/drive/{$item->id}";
    }
}
