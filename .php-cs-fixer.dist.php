<?php

if (!file_exists(__DIR__ . '/src') || !file_exists(__DIR__ . '/tests')) {
    exit(0);
}

$finder = (new \PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests']);

return (new \PhpCsFixer\Config())
    ->setRules(array(
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'phpdoc_to_comment' => false,
    ))
    ->setRiskyAllowed(true)
    ->setFinder($finder);
