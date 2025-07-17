<?php
require_once(__DIR__ . "/../onedrive.php");

function handle_route(): RequestResult
{
    // If we don't have a code in the query string (meaning that the user did not log in successfully
    // or did not grant privileges requested), we cannot proceed in obtaining an access token.
    if (!array_key_exists('code', $_GET))
        return Content::error("Authorization failed: Missing code.")->result();

    if (!($client = restore_onedrive_client_from_session()))
        return Content::error("Authorization failed: Unable to restore client state.")->result();

    obtain_onedrive_access_token($client, $_GET['code']);
    save_onedrive_client_state_to_session($client);

    return RequestResult::redirect("/drive");  // redirect to /drive
}
