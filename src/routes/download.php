<?php
require_once(__DIR__ . "/../onedrive.php");

function handle_route(): RequestResult
{
    if (!($client = restore_onedrive_client_from_session()))
        return RequestResult::redirect("/login");

    $parts = parse_url($_SERVER["REQUEST_URI"]);
    $file_path = get_file_path($parts["path"]);
    $item = get_drive_item($client, $file_path);

    if (get_drive_item_type($item) !== "file")
        return Content::error("error, not a file")->result();

    $file_content = $item->download()->getContents();

    save_onedrive_client_state_to_session($client);

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
