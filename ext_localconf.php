<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc']['retrostats'] = 'EXT:retrostats/class.tx_retrostats_hook.php:tx_retrostats_hook->statisticsInitHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['retrostats'] = 'EXT:retrostats/class.tx_retrostats_hook.php:tx_retrostats_hook->statisticsHook';

?>