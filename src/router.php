<?php
function route(): string
{
    $parts = parse_url($_SERVER["REQUEST_URI"]);

    $route = match ($parts["path"]) {
        "/index.php", "/" => "index.php",
        "/auth.php" => "auth.php",
        "/login.php" => "login.php",
        "/logout.php" => "logout.php",
    };

    error_log("==== $route");

    require(__DIR__ . "/routes/$route");

    return generate();
}
