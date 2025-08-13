<?php
declare(strict_types=1);

namespace PHPOneDrive;

require(__DIR__ . '/router.php');

function main(): void
{
    start_session();
    route($_SERVER["REQUEST_URI"], $_SERVER["REQUEST_METHOD"])->output();
}

function load_config(): array
{
    static $config = null;

    if ($config === null)
        $config = require($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    return $config;
}

function start_session(): void
{
    session_start(["cookie_samesite" => "lax"]);
}

function destroy_session(): void
{
    session_unset();
    session_destroy();
}

function is_session_active(): bool
{
    return session_status() === PHP_SESSION_ACTIVE;
}
