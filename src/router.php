<?php
declare(strict_types=1);

require_once(__DIR__ . '/request_result.php');

function route(): RequestResult
{
    $parts = parse_url($_SERVER["REQUEST_URI"]);
    $path_prefix = get_route_path_prefix($parts["path"]);

    $route = match ($path_prefix) {
        "/", "/drive", "/index" => "drive.php",
        "/download" => "download.php",
        "/auth" => "auth.php",
        "/login" => "login.php",
        "/logout" => "logout.php",
        default => null
    };

    if (!$route)
        return Content::error("Router error: Unknown route \"$parts[path]\".")->result();

    require(__DIR__ . "/routes/$route");

    $request_method = $_SERVER["REQUEST_METHOD"];
    $request_handler = "handle_{$request_method}_request";

    if (!function_exists($request_handler))
        return Content::error("Router error: Request handler not found for $request_method \"$parts[path]\".")->result();

    try {
        return $request_handler();
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
