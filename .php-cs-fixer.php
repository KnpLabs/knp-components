<?php
// see https://github.com/FriendsOfPHP/PHP-CS-Fixer

$finder = PhpCsFixer\Finder::create()
    ->exclude('vendor')
    ->in([__DIR__])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP80Migration:risky' => true,
        '@PHP81Migration' => true,
        '@PHPUnit100Migration:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => true,
        'declare_strict_types' => false,
        'php_unit_mock_short_will_return' => true,
        'no_extra_blank_lines' => true,
        'no_unused_imports' => true,
        'nullable_type_declaration_for_default_null_value' => true,
    ])
    ->setFinder($finder)
;
