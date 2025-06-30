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

    $content = show_files($client);

    // Persist the OneDrive client' state for next API requests.
    $_SESSION['onedrive.client.state'] = $client->getState();

    return $content;
}

function show_files(Krizalys\Onedrive\Client $client): string
{
    $rows = [];

    foreach ($client->getRoot()->getChildren() as $item) {
        $row = [];
        $row["name"] = $item->name;
        $row["type"] = $item->folder ? "folder" : "file";
        $row["modified"] = $item->lastModifiedDateTime->format(DateTimeInterface::RFC7231);

        if ($item->folder) {
            $row["type"] = "folder";

            $row["size"] = match ($item->folder->childCount) {
                0 => "empty",
                1 => "1 File",
                default => $item->folder->childCount . " Files",
            };
        } else if ($item->file) {
            $row["type"] = "file";
            $row["size"] = $item->size . " Bytes";
        }

        $rows[] = $row;
    }

    return use_template("files", ["rows" => $rows]);
}
