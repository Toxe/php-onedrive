<?php
declare(strict_types=1);

function handle_GET_request(): RequestResult
{
    destroy_session();

    return Content::success("Logged out.")->result();
}
