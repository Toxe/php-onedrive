<?php
require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/src/router.php');

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
    'cache' => __DIR__ . '/cache',
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

session_start(["cookie_samesite" => "lax"]);

echo use_template('main', ['content' => route(), 'header' => generate_header()]);


function use_template(string $template, array $values = []): string
{
    global $twig;
    return $twig->render("$template.html", $values);
}

function generate_header(): string
{
    $logged_in = array_key_exists('onedrive.client.state', $_SESSION);
    return use_template('header', ['logged_in' => $logged_in]);
}
