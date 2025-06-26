<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OneDrive Access from PHP</title>
</head>

<body>
    <p>
        <a href="/">/index</a><br />
        <a href="/logout.php">/logout</a>
    </p>

    <?php
    ($config = include __DIR__ . '/config.php') or die('Configuration file not found');
    require_once __DIR__ . '/vendor/autoload.php';

    use Krizalys\Onedrive\Onedrive;

    // Instantiates a OneDrive client bound to your OneDrive application.
    $client = Onedrive::client($config['ONEDRIVE_CLIENT_ID']);

    // Gets a log in URL with sufficient privileges from the OneDrive API.
    $url = $client->getLogInUrl([
        'files.read',
        'files.read.all',
        'files.readwrite',
        'files.readwrite.all',
        'offline_access',
    ], $config['ONEDRIVE_REDIRECT_URI']);

    error_log("----- INDEX --------------------------");
    session_start(["cookie_samesite" => "lax"]);

    // Persist the OneDrive client' state for next API requests.
    $_SESSION['onedrive.client.state'] = $client->getState();

    error_log("SESSION " . session_id() . ": " . print_r($_SESSION, 1));
    error_log("redirect --> $url");

    // Redirect the user to the log in URL.
    header('HTTP/1.1 302 Found', true, 302);
    header("Location: $url");
    ?>

</body>

</html>
