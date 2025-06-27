<?php
function generate(): string
{
    session_start(["cookie_samesite" => "lax"]);
    session_destroy();

    return "logged out";
}
