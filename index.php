<?php
require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/src/router.php');

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
    'cache' => __DIR__ . '/cache',
]);
$twig->addExtension(new \Twig\Extension\DebugExtension());

echo use_template('main', ['header' => generate_header(), 'content' => route()]);


function use_template(string $template, array $values = []): string
{
    global $twig;
    return $twig->render("$template.html", $values);
}

function generate_header(): string
{
    return use_template('header');
}
