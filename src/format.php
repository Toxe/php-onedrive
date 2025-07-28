<?php
declare(strict_types=1);

namespace PHPOneDrive;

function format_datetime(\DateTime $dt): string
{
    return $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i T');
}

function format_file_size(int $size): string
{
    $suffixes = [' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB'];

    if ($size == 0)
        return '0 Bytes';

    $e = (int) floor(log($size, 1024));
    return round($size / pow(1024, $e), 2) . $suffixes[$e];
}

function format_folder_size(int $children): string
{
    return match ($children) {
        0 => 'empty',
        1 => '1 File',
        default => "$children Files",
    };
}
