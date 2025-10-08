<?php

$finder = PhpCsFixer\Finder::create()->in([
    __DIR__ . '/src',
]);

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@Symfony' => true,
        '@PSR12' => true,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'combine_consecutive_unsets' => true,
        'single_quote' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['const', 'class', 'function'],
        ],
        'no_useless_else' => true,
        'phpdoc_order' => true,
        'header_comment' => [
            'header' => "This file is part of the proprietary project.\n\nThis file and its contents are confidential and protected by copyright law.\nUnauthorized copying, distribution, or disclosure of this content\nis strictly prohibited without prior written consent from the author or\ncopyright owner.\n\nFor the full copyright and license information, please view the LICENSE.md\nfile that was distributed with this source code.",
            'separate' => 'both'
        ]
    ])
    ->setFinder($finder);
