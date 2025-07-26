<?php
require_once(__DIR__ . "/../format.php");
require_once(__DIR__ . "/../onedrive.php");
require_once(__DIR__ . "/../template.php");

function handle_GET_request(): RequestResult
{
    if (!($client = restore_onedrive_client_from_session()))
        return RequestResult::redirect("/login");

    return display_drive_content($client, get_folder_from_url($client))->result();
}

function handle_POST_request(): RequestResult
{
    assert(isset($_POST["action"]));

    if (!($client = restore_onedrive_client_from_session()))
        return RequestResult::redirect("/login");

    $folder = get_folder_from_url($client);

    // handle form request
    $request_feedback = match ($_POST["action"]) {
        "rename" => handle_POST_rename_request($client),
        "delete" => handle_POST_delete_request($client),
        "upload" => handle_POST_upload_request($folder),
        "new_folder" => handle_POST_new_folder_request($folder),
    };

    return display_drive_content($client, $folder, $request_feedback)->result();
}

function get_folder_from_url(Krizalys\Onedrive\Client $client): Krizalys\Onedrive\Proxy\DriveItemProxy
{
    $parts = parse_url($_SERVER["REQUEST_URI"]);
    [$drive_id, $item_id] = parse_url_path($parts["path"]);

    if (empty($item_id))
        return $client->getRoot();
    else
        return get_drive_item($client, $item_id);
}

function display_drive_content(Krizalys\Onedrive\Client $client, Krizalys\Onedrive\Proxy\DriveItemProxy $folder, ?array $request_feedback = null): Content
{
    $files = collect_files($client, $folder);
    $breadcrumbs = collect_breadcrumbs($folder);

    save_onedrive_client_state_to_session($client);

    return Content::success(
        use_template("routes/drive", [
            "files" => $files,
            "breadcrumbs" => $breadcrumbs,
            "request_feedback" => $request_feedback,
        ])
    );
}

function collect_files(Krizalys\Onedrive\Client $client, Krizalys\Onedrive\Proxy\DriveItemProxy $folder): array
{
    $files = [];

    if ($folder->remoteItem)
        $folder = get_drive_item($client, $folder->remoteItem->id);

    foreach ($folder->getChildren() as $item) {
        $file_type = get_drive_item_type($item);

        $file = [];
        $file["id"] = $item->id;
        $file["url"] = build_drive_item_url($item);
        $file["name"] = $item->name;
        $file["type"] = $file_type;
        $file["modified_date"] = format_datetime($item->lastModifiedDateTime);
        $file["modified_by"] = $item->lastModifiedBy->user->displayName;
        $file["sharing"] = "private";
        $file["sharing_type"] = "sharing_private";

        if ($file_type === FileType::Folder) {
            $children = $item->remoteItem ? $item->remoteItem->folder->childCount : $item->folder->childCount;
            $file["size"] = format_folder_size($children);
            $file["icon"] = "folder";
            $file["type_description"] = "Drive Folder";

            if ($item->remoteItem) {
                $file["icon"] = "link";
                $file["type_description"] = "Linked Shared Folder";
            }
        } else if ($file_type === FileType::File) {
            $file["size"] = format_file_size($item->size);
            $file["icon"] = "file";
            $file["type_description"] = "Drive File";
        }

        if ($item->shared) {
            if ($item->shared->owner->user->displayName == $_SESSION["my_name"]) {
                $file["sharing"] = "shared by you";
                $file["sharing_type"] = "sharing_by_me";
            } else {
                $file["sharing"] = "shared by " . $item->shared->owner->user->displayName;
                $file["sharing_type"] = "sharing_by_other";
            }
        }

        $files[] = $file;
    }

    return $files;
}

function collect_breadcrumbs(Krizalys\Onedrive\Proxy\DriveItemProxy $folder): array
{
    $breadcrumbs = [];
    $breadcrumbs[] = ["name" => "Personal Drive", "url" => "/drive"];

    // show the names (without links) of parent folders of a remote folder
    if ($folder->parentReference->path) {
        $ret = preg_match('/^\/drives\/([[:xdigit:]]+)\/root:(.+)/', $folder->parentReference->path, $matches);

        if ($ret && count($matches) === 3) {
            $parts = explode('/', $matches[2]);
            array_shift($parts);  // first element is empty

            foreach ($parts as $name)
                $breadcrumbs[] = ["name" => urldecode($name), "url" => null];
        }
    }

    // URLs to parent and current folders (unless this is the root folder)
    if (!$folder->root) {
        $last = count($breadcrumbs) - 1;

        if ($last > 0)
            $breadcrumbs[$last]["url"] = "/drive/{$folder->parentReference->id}";

        $breadcrumbs[] = ["name" => urldecode($folder->name), "url" => build_drive_item_url($folder)];
    }

    return $breadcrumbs;
}

function parse_url_path(string $url_path): array
{
    $ret = preg_match('/^\/drive\/([[:xdigit:]]+)!([[:alnum:]]+)/', $url_path, $matches);

    if (!$ret || count($matches) !== 3)
        return [null, null];

    return [$matches[1], "$matches[1]!$matches[2]"];
}

function handle_POST_rename_request(Krizalys\Onedrive\Client $client): array
{
    if (!isset($_POST["new_name"]) || !isset($_POST["item_id"]))
        return [false, "Request error."];

    $item = $client->getDriveItemById($_POST["item_id"]);
    $item->rename($_POST["new_name"]);

    $msg = match (get_drive_item_type($item)) {
        FileType::File => "File renamed.",
        FileType::Folder => "Folder renamed.",
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
        FileType::File => "File deleted.",
        FileType::Folder => "Folder deleted.",
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
