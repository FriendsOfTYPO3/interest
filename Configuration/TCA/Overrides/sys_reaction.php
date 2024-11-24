<?php

defined('TYPO3') || die();

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('reactions')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'sys_reaction',
        'reaction_type',
        [
            'label' => \Pixelant\Interest\Reaction\CreateUpdateDeleteReaction::getDescription(),
            'value' => \Pixelant\Interest\Reaction\CreateUpdateDeleteReaction::getType(),
            'icon' => \Pixelant\Interest\Reaction\CreateUpdateDeleteReaction::getIconIdentifier(),
        ]
    );
}
