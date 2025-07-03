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
    $files = collect_files($folder, $path);
    $breadcrumbs = collect_breadcrumbs($path);

    // Persist the OneDrive client' state for next API requests.
    $_SESSION['onedrive.client.state'] = $client->getState();

    return use_template("drive", ["files" => $files, "breadcrumbs" => $breadcrumbs]);
}

function open_folder(Krizalys\Onedrive\Client $client, string $path): Krizalys\Onedrive\Proxy\DriveItemProxy
{
    if ($path === '/')
        return $client->getRoot();
    else
        return $client->getDriveItemByPath($path);
}

function collect_files(Krizalys\Onedrive\Proxy\DriveItemProxy $folder, string $path): array
{
    $files = [];

    foreach ($folder->getChildren() as $item) {
        $file = [];
        $file["id"] = $item->id;
        $file["url"] = build_item_url($item, $path);
        $file["name"] = $item->name;
        $file["type"] = $item->folder ? "folder" : "file";
        $file["modified"] = $item->lastModifiedDateTime->format(DateTimeInterface::RFC7231);

        if ($item->folder) {
            $file["type"] = "folder";

            $file["size"] = match ($item->folder->childCount) {
                0 => "empty",
                1 => "1 File",
                default => $item->folder->childCount . " Files",
            };
        } else if ($item->file) {
            $file["type"] = "file";
            $file["size"] = $item->size . " Bytes";
        }

        $files[] = $file;
    }

    return $files;
}

function collect_breadcrumbs(string $path): array
{
    if ($path === '/')
        return [];

    $url = "/drive";
    $breadcrumbs = [];
    $parts = explode('/', $path);
    array_shift($parts);

    foreach ($parts as $name) {
        $url .= "/$name";
        $breadcrumbs[] = ["name" => $name, "url" => $url];
    }

    return $breadcrumbs;
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
