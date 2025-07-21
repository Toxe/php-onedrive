<?php
require_once(__DIR__ . "/../onedrive.php");
require_once(__DIR__ . "/../template.php");

function handle_GET_request(): RequestResult
{
    if (!($client = restore_onedrive_client_from_session()))
        return RequestResult::redirect("/login");

    [$path, $folder] = start_request_handling($client);

    return finish_request_handling($client, $folder, $path)->result();
}

function handle_POST_request(): RequestResult
{
    assert(isset($_POST["action"]));

    if (!($client = restore_onedrive_client_from_session()))
        return RequestResult::redirect("/login");

    [$path, $folder] = start_request_handling($client);

    // handle form request
    $request_feedback = match ($_POST["action"]) {
        "rename" => handle_POST_rename_request($client),
        "delete" => handle_POST_delete_request($client),
        "upload" => handle_POST_upload_request($folder),
        "new_folder" => handle_POST_new_folder_request($folder),
    };

    return finish_request_handling($client, $folder, $path, $request_feedback)->result();
}

function start_request_handling(Krizalys\Onedrive\Client $client): array
{
    $parts = parse_url($_SERVER["REQUEST_URI"]);
    $path = get_drive_path($parts["path"]);
    $folder = get_drive_item($client, $path);

    return [$path, $folder];
}

function finish_request_handling(Krizalys\Onedrive\Client $client, Krizalys\Onedrive\Proxy\DriveItemProxy $folder, string $path, ?array $request_feedback = null): Content
{
    $files = collect_files($folder, $path);
    $breadcrumbs = collect_breadcrumbs($path);

    save_onedrive_client_state_to_session($client);

    return Content::success(
        use_template("routes/drive", [
            "files" => $files,
            "breadcrumbs" => $breadcrumbs,
            "request_feedback" => $request_feedback,
        ])
    );
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
        $file["modified_date"] = format_datetime($item->lastModifiedDateTime);
        $file["modified_by"] = $item->lastModifiedBy->user->displayName;

        if ($item->folder) {
            $file["type"] = "folder";

            $file["size"] = match ($item->folder->childCount) {
                0 => "empty",
                1 => "1 File",
                default => $item->folder->childCount . " Files",
            };
        } else if ($item->file) {
            $file["type"] = "file";
            $file["size"] = format_size($item->size);
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

function handle_POST_rename_request(Krizalys\Onedrive\Client $client): array
{
    if (!isset($_POST["new_name"]) || !isset($_POST["item_id"]))
        return [false, "Request error."];

    $item = $client->getDriveItemById($_POST["item_id"]);
    $item->rename($_POST["new_name"]);

    $msg = match (get_drive_item_type($item)) {
        "file" => "File renamed.",
        "folder" => "Folder renamed.",
    };

    return [true, $msg];
}

function handle_POST_delete_request(Krizalys\Onedrive\Client $client): array
{
    if (!isset($_POST["item_id"]))
        return [false, "Request error."];

    $item = $client->getDriveItemById($_POST["item_id"]);
    $item->delete();

    $msg = match (get_drive_item_type($item)) {
        "file" => "File deleted.",
        "folder" => "Folder deleted.",
    };

    return [true, $msg];
}

function handle_POST_upload_request(Krizalys\Onedrive\Proxy\DriveItemProxy $folder): array
{
    if (!isset($_FILES["file"]))
        return [false, "Request error."];

    if (($content = file_get_contents($_FILES["file"]["tmp_name"])) === false)
        return [false, "Request error."];

    $folder->upload($_FILES["file"]["name"], $content);

    return [true, "File uploaded."];
}

function handle_POST_new_folder_request(Krizalys\Onedrive\Proxy\DriveItemProxy $folder): array
{
    if (!isset($_POST["folder_name"]))
        return [false, "Request error."];

    $folder->createFolder($_POST["folder_name"]);

    return [true, "Folder created."];
}

function format_datetime(DateTime $dt): string
{
    return $dt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i T');
}

function format_size(int $size): string
{
    $suffixes = [' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB'];

    if ($size == 0)
        return '0 Bytes';

    $e = (int) floor(log($size, 1024));
    return round($size / pow(1024, $e), 2) . $suffixes[$e];
}
