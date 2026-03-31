<?php

$dirs = array_filter([
    __DIR__ . '/src',
    __DIR__ . '/tests',
    __DIR__ . '/forms',
], 'is_dir');

$finder = PhpCsFixer\Finder::create()
    ->in($dirs)
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'single_quote' => true,
        'array_syntax' => ['syntax' => 'short'],
        'trailing_comma_in_multiline' => true,
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);
