<?php
function generate(): string
{
    session_unset();
    session_destroy();

    return "logged out";
}
