<?php

defined('TYPO3') || die();

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('reactions')) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
        'sys_reaction',
        'reaction_type',
        [
            'label' => \FriendsOfTYPO3\Interest\Reaction\CreateUpdateDeleteReaction::getDescription(),
            'value' => \FriendsOfTYPO3\Interest\Reaction\CreateUpdateDeleteReaction::getType(),
            'icon' => \FriendsOfTYPO3\Interest\Reaction\CreateUpdateDeleteReaction::getIconIdentifier(),
        ]
    );

    $GLOBALS['TCA']['sys_reaction']['ctrl']['typeicon_classes'][\FriendsOfTYPO3\Interest\Reaction\CreateUpdateDeleteReaction::getType()] = \FriendsOfTYPO3\Interest\Reaction\CreateUpdateDeleteReaction::getIconIdentifier();

    $GLOBALS['TCA']['sys_reaction']['palettes']['interestCreateUpdateDelete'] = [
        'label' => 'LLL:EXT:reactions/Resources/Private/Language/locallang_db.xlf:palette.additional',
        'showitem' => 'impersonate_user',
    ];

    $GLOBALS['TCA']['sys_reaction']['types'][\FriendsOfTYPO3\Interest\Reaction\CreateUpdateDeleteReaction::getType()] = [
        'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
        --palette--;;config,
        --palette--;;interestCreateUpdateDelete,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;access',
        'columnsOverrides' => [
            'impersonate_user' => [
                'config' => [
                    'minitems' => 1,
                ],
            ],
        ],
    ];
}
