<?php
require_once(__DIR__ . "/../onedrive.php");

function handle_GET_request(): RequestResult
{
    if (!($client = restore_onedrive_client_from_session()))
        return RequestResult::redirect("/login");

    $parts = parse_url($_SERVER["REQUEST_URI"]);
    [$drive_id, $item_id, $filename] = parse_url_path($parts["path"]);
    $item = get_drive_item($client, $item_id);

    if (get_drive_item_type($item) !== FileType::File)
        return Content::error("Unable to download, \"{$item->name}\" is not a file.")->result();

    $file_content = $item->download()->getContents();

    save_onedrive_client_state_to_session($client);

    return RequestResult::download($file_content, $item->file->mimeType, $item->size, $item->name);
}

function parse_url_path(string $url_path): array
{
    $ret = preg_match('/^\/download\/([[:xdigit:]]+)!([[:alnum:]]+)\/(.+)/', $url_path, $matches);

    if (!$ret || count($matches) !== 4)
        return [null, null];

    return [$matches[1], "$matches[1]!$matches[2]", $matches[3]];
}
