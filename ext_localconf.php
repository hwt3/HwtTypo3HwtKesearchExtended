<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TeaminmediasPluswerk\\KeSearch\\Controller\\BackendModuleController'] = array(
    'className' => 'Hwt\\HwtKesearchExtended\\Xclass\\BackendModuleController'
);


// Register page icon
if (TYPO3_MODE === 'BE') {
    // For TYPO3 7.x, older version just need tca.php codes
    if ( version_compare(TYPO3_version, '7.0.0') >= 0 ) {
        /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\IconRegistry');

        $iconRegistry->registerIcon(
            'apps-pagetree-folder-contains-kesearch',
            'TYPO3\\CMS\\Core\\Imaging\IconProvider\\BitmapIconProvider',
            array('source' => 'EXT:ke_search/ext_icon.gif')
        );
    }
}