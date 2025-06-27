<?php
use Krizalys\Onedrive\Onedrive;

function generate(): string
{
    session_start(["cookie_samesite" => "lax"]);

    $config = require($_SERVER['DOCUMENT_ROOT'] . '/config.php');

    if (!array_key_exists('onedrive.client.state', $_SESSION)) {
        // not logged in -> redirect to login.php
        header('HTTP/1.1 302 Found', true, 302);
        header("Location: /login.php");
        return "logging in...";
    }

    $client = Onedrive::client(
        $config['ONEDRIVE_CLIENT_ID'],
        [
            'state' => $_SESSION['onedrive.client.state'],  // Restore the previous state while instantiating this client to proceed in obtaining an access token.
        ]
    );

    // Past this point, you can start using file/folder functions from the SDK, eg:
    $file = $client->getRoot()->upload('hello.txt', 'Hello World!');
    $content = $file->download();
    $file->delete();

    // Persist the OneDrive client' state for next API requests.
    $_SESSION['onedrive.client.state'] = $client->getState();

    return $content;
}
