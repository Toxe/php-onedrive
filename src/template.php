<?php
declare(strict_types=1);

namespace PHPOneDrive;

function use_template(string $template, array $values = []): string
{
    static $twig = null;

    if ($twig === null) {
        $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
        $twig = new \Twig\Environment($loader, ['debug' => true, 'cache' => __DIR__ . '/../cache']);
        $twig->addExtension(new \Twig\Extension\DebugExtension());
    }

    return $twig->render("$template.html", $values);
}
