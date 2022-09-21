<?php

use PhpCsFixer\Finder;
use PhpCsFixer\Config;
use PhpCsFixer\Fixer\DoctrineAnnotation\DoctrineAnnotationBracesFixer;

$finder = (new Finder())
    ->in(__DIR__)
    ->exclude('var')
;

$defaultIgnoredTags = (new DoctrineAnnotationBracesFixer())
    ->getConfigurationDefinition()
    ->getOptions()[0]
    ->getDefault()
;

return (new Config())
    ->setRules([
        '@Symfony' => true
    ])
    ->setFinder($finder)
;
