<?php
require_once(__DIR__ . "/../onedrive.php");

function handle_route(): RequestResult
{
    $config = require($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    // not logged in -> redirect to /login
    if (!array_key_exists('onedrive.client.state', $_SESSION))
        return RequestResult::redirect("/login");

    $client = Krizalys\Onedrive\Onedrive::client(
        $config['ONEDRIVE_CLIENT_ID'],
        [
            'state' => $_SESSION['onedrive.client.state'],  // Restore the previous state while instantiating this client to proceed in obtaining an access token.
        ]
    );

    $parts = parse_url($_SERVER["REQUEST_URI"]);
    $file_path = get_file_path($parts["path"]);
    $item = get_drive_item($client, $file_path);

    if (get_drive_item_type($item) !== "file")
        return Content::error("error, not a file")->result();

    $file_content = $item->download()->getContents();

    // Persist the OneDrive client' state for next API requests.
    $_SESSION['onedrive.client.state'] = $client->getState();

    return RequestResult::download($file_content, $item->file->mimeType, $item->size, $item->name);
}

function get_file_path(string $url_path): string
{
    if (!str_starts_with($url_path, "/download"))
        return '/';

    $path = substr($url_path, strlen("/download"));

    if ($path !== '/' && str_ends_with($path, '/'))
        $path = rtrim($path, '/');

    return $path === '' ? '/' : $path;
}
