<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

include_once(TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Overrides/tx_kesearch_indexerconfig.php');