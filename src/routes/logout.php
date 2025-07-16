<?php
function handle_route(): RequestResult
{
    session_unset();
    session_destroy();

    return Content::success("Logged out.")->result();
}
