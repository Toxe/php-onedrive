<?php
function handle_route(): RequestResult
{
    $config = require($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    // If we don't have a code in the query string (meaning that the user did not
    // log in successfully or did not grant privileges requested), we cannot proceed
    // in obtaining an access token.
    if (!array_key_exists('code', $_GET))
        die('code undefined in $_GET');

    // Attempt to load the OneDrive client' state persisted from the previous request.
    if (!array_key_exists('onedrive.client.state', $_SESSION))
        die('onedrive.client.state undefined in $_SESSION');

    $client = Krizalys\Onedrive\Onedrive::client(
        $config['ONEDRIVE_CLIENT_ID'],
        [
            'state' => $_SESSION['onedrive.client.state'],  // Restore the previous state while instantiating this client to proceed in obtaining an access token.
        ]
    );

    // Obtain the token using the code received by the OneDrive API.
    $client->obtainAccessToken($config['ONEDRIVE_CLIENT_SECRET'], $_GET['code']);

    // Persist the OneDrive client' state for next API requests.
    $_SESSION['onedrive.client.state'] = $client->getState();

    // redirect to /drive
    return RequestResult::redirect("/drive");
}
