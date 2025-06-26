<p>
    <a href="/">/index</a><br />
    <a href="/logout.php">/logout</a>
</p>

<?php
($config = include __DIR__ . '/config.php') or die('Configuration file not found');
require_once __DIR__ . '/vendor/autoload.php';

use Krizalys\Onedrive\Onedrive;

error_log("----- AUTH --------------------------");
error_log("GET: " . print_r($_GET, 1));

// If we don't have a code in the query string (meaning that the user did not
// log in successfully or did not grant privileges requested), we cannot proceed
// in obtaining an access token.
if (!array_key_exists('code', $_GET)) {
    die('code undefined in $_GET');
}

session_start(["cookie_samesite" => "lax"]);
error_log("SESSION " . session_id() . ": " . print_r($_SESSION, 1));

// Attempt to load the OneDrive client' state persisted from the previous request.
if (!array_key_exists('onedrive.client.state', $_SESSION)) {
    die('onedrive.client.state undefined in $_SESSION');
}

$client = Onedrive::client(
    $config['ONEDRIVE_CLIENT_ID'],
    [
        // Restore the previous state while instantiating this client to proceed
        // in obtaining an access token.
        'state' => $_SESSION['onedrive.client.state'],
    ]
);

// Obtain the token using the code received by the OneDrive API.
$client->obtainAccessToken($config['ONEDRIVE_CLIENT_SECRET'], $_GET['code']);

// Persist the OneDrive client' state for next API requests.
$_SESSION['onedrive.client.state'] = $client->getState();

// Past this point, you can start using file/folder functions from the SDK, eg:
$file = $client->getRoot()->upload('hello.txt', 'Hello World!');
echo $file->download();
$file->delete();
?>
