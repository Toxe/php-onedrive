<?php
declare(strict_types=1);

namespace PHPOneDrive\Route;

require_once(__DIR__ . "/../onedrive.php");

function handle_GET_request(): \PHPOneDrive\RequestResult
{
    if (!($client = \PHPOneDrive\restore_onedrive_client_from_session()))
        return \PHPOneDrive\RequestResult::redirect("/login");

    $parts = parse_url($_SERVER["REQUEST_URI"]);
    [$drive_id, $item_id, $filename] = parse_url_path($parts["path"]);
    $item = \PHPOneDrive\get_drive_item($client, $item_id);

    if (\PHPOneDrive\get_drive_item_type($item) !== \PHPOneDrive\FileType::File)
        return \PHPOneDrive\Content::error("Unable to download, \"{$item->name}\" is not a file.")->result();

    $file_content = $item->download()->getContents();

    \PHPOneDrive\save_onedrive_client_state_to_session($client);

    return \PHPOneDrive\RequestResult::download($file_content, $item->file->mimeType, $item->size, $item->name);
}

function parse_url_path(string $url_path): array
{
    $ret = preg_match('/^\/download\/([[:xdigit:]]+)!([[:alnum:]]+)\/(.+)/', $url_path, $matches);

    if (!$ret || count($matches) !== 4)
        return [null, null];

    return [$matches[1], "$matches[1]!$matches[2]", $matches[3]];
}
