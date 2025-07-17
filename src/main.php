<?php
require(__DIR__ . '/router.php');

function main(): void
{
    session_start(["cookie_samesite" => "lax"]);
    route()->output();
}

function load_config(): array
{
    static $config = null;

    if ($config === null)
        $config = require($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    return $config;
}
