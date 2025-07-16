<?php
require_once(__DIR__ . "/../onedrive.php");
require_once(__DIR__ . "/../template.php");

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
    $path = get_drive_path($parts["path"]);
    $folder = get_drive_item($client, $path);

    // handle form requests
    $request_feedback = null;

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        assert(isset($_POST["action"]));

        $request_feedback = match ($_POST["action"]) {
            "rename" => handle_rename_request($client),
            "delete" => handle_delete_request($client),
            "upload" => handle_upload_request($folder),
            "new_folder" => handle_new_folder_request($folder),
        };
    }

    // generate page content
    $files = collect_files($folder, $path);
    $breadcrumbs = collect_breadcrumbs($path);

    // Persist the OneDrive client' state for next API requests.
    $_SESSION['onedrive.client.state'] = $client->getState();

    $content = use_template("drive", ["files" => $files, "breadcrumbs" => $breadcrumbs, "request_feedback" => $request_feedback]);
    return Content::success($content)->result();
}

function collect_files(Krizalys\Onedrive\Proxy\DriveItemProxy $folder, string $path): array
{
    $files = [];

    foreach ($folder->getChildren() as $item) {
        $file = [];
        $file["id"] = $item->id;
        $file["url"] = build_drive_item_url($item, $path);
        $file["name"] = $item->name;
        $file["type"] = get_drive_item_type($item);
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

    return match (get_drive_item_type($item)) {
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

    return match (get_drive_item_type($item)) {
        "file" => "File deleted.",
        "folder" => "Folder deleted.",
    };
}

function handle_upload_request(Krizalys\Onedrive\Proxy\DriveItemProxy $folder): string
{
    if (!isset($_FILES["file"]))
        return "request error";

    if (($content = file_get_contents($_FILES["file"]["tmp_name"])) === false)
        return "request error";

    $folder->upload($_FILES["file"]["name"], $content);

    return "File uploaded.";
}

function handle_new_folder_request(Krizalys\Onedrive\Proxy\DriveItemProxy $folder): string
{
    if (!isset($_POST["folder_name"]))
        return "request error";

    $folder->createFolder($_POST["folder_name"]);

    return "Folder created.";
}
