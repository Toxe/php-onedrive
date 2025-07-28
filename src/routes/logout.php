<?php
declare(strict_types=1);

namespace PHPOneDrive\Route;

function handle_GET_request(): \PHPOneDrive\RequestResult
{
    \PHPOneDrive\destroy_session();

    return \PHPOneDrive\Content::success("Logged out.")->result();
}
