<?php
require(__DIR__ . '/router.php');

function main(): void
{
    session_start(["cookie_samesite" => "lax"]);
    route()->output();
}
