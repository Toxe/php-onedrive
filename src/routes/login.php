<?php
declare(strict_types=1);

namespace PHPOneDrive\Route;

require_once(__DIR__ . "/../onedrive.php");

function handle_GET_request(): \PHPOneDrive\RequestResult
{
    [$client, $login_url] = \PHPOneDrive\init_onedrive_client();
    \PHPOneDrive\save_onedrive_client_state_to_session($client);

    return \PHPOneDrive\RequestResult::redirect($login_url);  // redirect to login URL
}
