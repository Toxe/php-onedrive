<?php
require_once(__DIR__ . '/request_result.php');

function route(): RequestResult
{
    $parts = parse_url($_SERVER["REQUEST_URI"]);
    $path_prefix = get_route_path_prefix($parts["path"]);

    $route = match ($path_prefix) {
        "/", "/drive", "/index" => "drive.php",
        "/auth" => "auth.php",
        "/login" => "login.php",
        "/logout" => "logout.php",
        default => null
    };

    if (!$route)
        return Content::error("Unknown route: " . $parts["path"])->result();

    require(__DIR__ . "/routes/$route");

    try {
        return handle_route();
    }
    catch (Exception $e) {
        error_log($e->getMessage());
        return Content::error(str_replace("\\n", "\n", $e->getMessage()))->result();
    }
}

function get_route_path_prefix(string $path): string
{
    if (($pos = strpos($path, '/', 1)) === false)
        return $path;

    return substr($path, 0, $pos);
}
