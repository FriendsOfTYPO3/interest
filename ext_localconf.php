<?php

use FriendsOfTYPO3\Interest\Hook\ProcessCmdmap;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use FriendsOfTYPO3\Interest\Updates\RemovePendingRelationsWithEmptyRemoteIdUpdateWizard;
defined('TYPO3') or die('Access denied.');

(static function () {
    $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['interest']
        = ProcessCmdmap::class;
})();
