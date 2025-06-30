<?php
require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/src/router.php');

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'debug' => true,
    'cache' => __DIR__ . '/cache',
]);

echo $twig->render('main.html', ['content' => route()]);
