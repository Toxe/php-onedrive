<?php
function route(): string
{
    $parts = parse_url($_SERVER["REQUEST_URI"]);
    $path_prefix = get_path_prefix($parts["path"]);

    $route = match ($path_prefix) {
        "/", "/drive", "/index" => "drive.php",
        "/auth" => "auth.php",
        "/login" => "login.php",
        "/logout" => "logout.php",
        default => null
    };

    if (!$route)
        return use_template("error", ["message" => "Unknown route: " . $parts["path"]]);

    error_log("==== $route");

    require(__DIR__ . "/routes/$route");

    $content = "";

    try {
        $content = generate();
    }
    catch (Exception $e) {
        error_log($e->getMessage());
        $content = use_template("error", ["message" => str_replace('\n', "\n", $e->getMessage())]);
    }

    return $content;
}

function get_path_prefix(string $path): string
{
    if (($pos = strpos($path, '/', 1)) === FALSE)
        return $path;

    return substr($path, 0, $pos);
}
