<?php

/** @noinspection PhpUndefinedNamespaceInspection */
$config = \TYPO3\CodingStandards\CsFixerConfig::create()->addRules([
    'single_line_empty_body' => false,
]);
$config->getFinder()->in('Classes')->in('Configuration')->in('Tests');
return $config;
