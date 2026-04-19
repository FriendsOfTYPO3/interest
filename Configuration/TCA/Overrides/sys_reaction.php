<?php

declare(strict_types=1);

use FriendsOfTYPO3\Interest\Reaction\CreateUpdateDeleteReaction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

if (ExtensionManagementUtility::isLoaded('reactions')) {
    ExtensionManagementUtility::addTcaSelectItem(
        'sys_reaction',
        'reaction_type',
        [
            'label' => CreateUpdateDeleteReaction::getDescription(),
            'value' => CreateUpdateDeleteReaction::getType(),
            'icon' => CreateUpdateDeleteReaction::getIconIdentifier(),
        ]
    );

    $GLOBALS['TCA']['sys_reaction']['ctrl']['typeicon_classes'][CreateUpdateDeleteReaction::getType()] = CreateUpdateDeleteReaction::getIconIdentifier();

    $GLOBALS['TCA']['sys_reaction']['palettes']['interestCreateUpdateDelete'] = [
        'label' => 'reactions.db:palette.additional',
        'showitem' => 'impersonate_user',
    ];

    $GLOBALS['TCA']['sys_reaction']['types'][CreateUpdateDeleteReaction::getType()] = [
        'showitem' => '
        --div--;core.form.tabs:general,
        --palette--;;config,
        --palette--;;interestCreateUpdateDelete,
        --div--;core.form.tabs:access,
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
