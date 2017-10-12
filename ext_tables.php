<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}


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
            0 => 'LLL:EXT:hwt_kesearch_extended/Resources/Private/Language/locallang_be.xlf:folder',
            1 => 'kesearch',
            2 => 'apps-pagetree-folder-contains-kesearch'
        );
    }
}