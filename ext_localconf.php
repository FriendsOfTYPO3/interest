<?php

use FriendsOfTYPO3\Interest\Hook\ProcessCmdmap;

defined('TYPO3') || die('Access denied.');

(static function () {
    $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['interest']
        = ProcessCmdmap::class;
})();
