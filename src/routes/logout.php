<?php
function handle_GET_request(): RequestResult
{
    destroy_session();

    return Content::success("Logged out.")->result();
}
