<?php
function handle_route(): RequestResult
{
    destroy_session();

    return Content::success("Logged out.")->result();
}
