<?php
function generate(): string
{
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

    // handle form requests
    $request_result = null;

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        assert(isset($_POST["action"]));

        $request_result = match ($_POST["action"]) {
            "rename" => handle_rename_request($client),
            "delete" => handle_delete_request($client),
        };
    }

    // generate page content
    $parts = parse_url($_SERVER["REQUEST_URI"]);
    $path = get_drive_path($parts["path"]);

    $folder = open_folder($client, $path);
    $files = collect_files($folder, $path);
    $breadcrumbs = collect_breadcrumbs($path);

    // Persist the OneDrive client' state for next API requests.
    $_SESSION['onedrive.client.state'] = $client->getState();

    return use_template("drive", ["files" => $files, "breadcrumbs" => $breadcrumbs, "request_result" => $request_result]);
}

function open_folder(Krizalys\Onedrive\Client $client, string $path): Krizalys\Onedrive\Proxy\DriveItemProxy
{
    if ($path === '/')
        return $client->getRoot();
    else
        return $client->getDriveItemByPath($path);
}

function get_item_type(Krizalys\Onedrive\Proxy\DriveItemProxy $item): string
{
    return $item->folder ? "folder" : "file";
}

function collect_files(Krizalys\Onedrive\Proxy\DriveItemProxy $folder, string $path): array
{
    $files = [];

    foreach ($folder->getChildren() as $item) {
        $file = [];
        $file["id"] = $item->id;
        $file["url"] = build_item_url($item, $path);
        $file["name"] = $item->name;
        $file["type"] = get_item_type($item);
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
        return [["name" => "Drive", "url" => "/drive"]];

    $parts = explode('/', $path);
    array_shift($parts);

    $url = "/drive";
    $breadcrumbs = [];
    $breadcrumbs[] = ["name" => "Drive", "url" => $url];

    foreach ($parts as $name) {
        $url .= "/$name";
        $breadcrumbs[] = ["name" => urldecode($name), "url" => $url];
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

function handle_rename_request(Krizalys\Onedrive\Client $client): string
{
    if (!isset($_POST["new_name"]) || !isset($_POST["item_id"]))
        return "request error";

    $item = $client->getDriveItemById($_POST["item_id"]);
    $item->rename($_POST["new_name"]);

    return match (get_item_type($item)) {
        "file" => "File renamed.",
        "folder" => "Folder renamed.",
    };
}

function handle_delete_request(Krizalys\Onedrive\Client $client): string
{
    if (!isset($_POST["item_id"]))
        return "request error";

    $item = $client->getDriveItemById($_POST["item_id"]);
    $item->delete();

    return match (get_item_type($item)) {
        "file" => "File deleted.",
        "folder" => "Folder deleted.",
    };
}
