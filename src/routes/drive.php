<?php
function generate(): string
{
    session_start(["cookie_samesite" => "lax"]);

    $config = require($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    if (!array_key_exists('onedrive.client.state', $_SESSION)) {
        // not logged in -> redirect to /login
        header('HTTP/1.1 302 Found', true, 302);
        header("Location: /login");
        return "logging in...";
    }

    $client = Krizalys\Onedrive\Onedrive::client(
        $config['ONEDRIVE_CLIENT_ID'],
        [
            'state' => $_SESSION['onedrive.client.state'],  // Restore the previous state while instantiating this client to proceed in obtaining an access token.
        ]
    );

    $parts = parse_url($_SERVER["REQUEST_URI"]);
    $path = get_drive_path($parts["path"]);

    $folder = open_folder($client, $path);
    $content = show_files($folder, $path);

    // Persist the OneDrive client' state for next API requests.
    $_SESSION['onedrive.client.state'] = $client->getState();

    return $content;
}

function open_folder(Krizalys\Onedrive\Client $client, string $path): Krizalys\Onedrive\Proxy\DriveItemProxy
{
    if ($path === '/')
        return $client->getRoot();
    else
        return $client->getDriveItemByPath($path);
}

function show_files(Krizalys\Onedrive\Proxy\DriveItemProxy $folder, string $path): string
{
    $rows = [];

    foreach ($folder->getChildren() as $item) {
        $row = [];
        $row["id"] = $item->id;
        $row["url"] = build_item_url($item, $path);
        $row["name"] = $item->name;
        $row["type"] = $item->folder ? "folder" : "file";
        $row["modified"] = $item->lastModifiedDateTime->format(DateTimeInterface::RFC7231);

        if ($item->folder) {
            $row["type"] = "folder";

            $row["size"] = match ($item->folder->childCount) {
                0 => "empty",
                1 => "1 File",
                default => $item->folder->childCount . " Files",
            };
        } else if ($item->file) {
            $row["type"] = "file";
            $row["size"] = $item->size . " Bytes";
        }

        $rows[] = $row;
    }

    return use_template("files", ["rows" => $rows, "path" => $path]);
}

function build_item_url(Krizalys\Onedrive\Proxy\DriveItemProxy $item, string $path): string
{
    $slash = $path === '/' ? '' : '/';
    return "/drive{$path}{$slash}{$item->name}";
}

function get_drive_path(string $url_path): string
{
    if (!str_starts_with($url_path, "/drive"))
        return '/';

    $path = substr($url_path, strlen("/drive"));

    if ($path !== '/' && str_ends_with($path, '/'))
        $path = rtrim($path, '/');

    return $path === '' ? '/' : $path;
}
