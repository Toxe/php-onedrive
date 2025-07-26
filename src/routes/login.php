<?php
declare(strict_types=1);

require_once(__DIR__ . "/../onedrive.php");

function handle_GET_request(): RequestResult
{
    [$client, $login_url] = init_onedrive_client();
    save_onedrive_client_state_to_session($client);

    return RequestResult::redirect($login_url);  // redirect to login URL
}
