<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

include_once(TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/TCA/Overrides/tx_kesearch_indexerconfig.php');


// Add folder icon
if (TYPO3_MODE === 'BE') {
    /*
     * Mount ke_search page icon
     */
    if ( version_compare(TYPO3_version, '7.0.0') >= 0 ) {
            // TYPO3 7.x
        $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes']['contains-kesearch'] = 'apps-pagetree-folder-contains-kesearch';

        // add select option for ke_search
        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
            0 => 'LLL:EXT:ke_search/mod1/locallang.xlf:title',
            1 => 'kesearch',
            2 => 'apps-pagetree-folder-contains-kesearch'
        );
    }
    else {
            // TYPO3 6.x
        $folderName = 'kesearch';
        $folderPath = '../typo3conf/ext/ke_search/ext_icon.gif';

        unset($GLOBALS['ICON_TYPES'][$folderName]);

        \TYPO3\CMS\Backend\Sprite\SpriteManager::addTcaTypeIcon('pages', 'contains-'.$folderName, $folderPath);

        $GLOBALS['TCA']['pages']['columns']['module']['config']['items'][] = array(
            0 => 'LLL:EXT:ke_search/mod1/locallang.xlf:title',
            1 => $folderName,
            2 => $folderPath
        );
    }
}