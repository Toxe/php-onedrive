<?php
function get_drive_item(Krizalys\Onedrive\Client $client, string $path): Krizalys\Onedrive\Proxy\DriveItemProxy
{
    if ($path === '/')
        return $client->getRoot();
    else
        return $client->getDriveItemByPath($path);
}

function get_drive_item_type(Krizalys\Onedrive\Proxy\DriveItemProxy $item): string
{
    return $item->folder ? "folder" : "file";
}

function build_drive_item_url(Krizalys\Onedrive\Proxy\DriveItemProxy $item, string $path): string
{
    $type = get_drive_item_type($item);
    $prefix = $type === "file" ? "download" : "drive";
    $slash = $path === '/' ? '' : '/';
    return "/{$prefix}{$path}{$slash}{$item->name}";
}
