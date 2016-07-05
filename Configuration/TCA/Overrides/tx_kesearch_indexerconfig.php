<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

/*
 * Modify tca of ke_search indexerconfig for versions lower than 2.1.0
 */
$ke_search_version = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('ke_search');
if ( version_compare($ke_search_version, '2.1.0') < 1 ) {
    $GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['storagepid']['config']['wizards'] = $GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['targetpid']['config']['wizards'] = $GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['startingpoints_recursive']['config']['wizards'] = $GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['single_pages']['config']['wizards'] = $GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['sysfolder']['config']['wizards'] = array(
        'suggest' => array(
            'type' => 'suggest',
        ),
    );
}


// Load extension manager configuration
$emConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['hwt_kesearch_extended']);

// Remove indexer types set in extension settings
foreach(explode(',', $emConfiguration['removeIndexerTypes']) as $removeIndexerType) {
    unset($GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['type']['config']['items'][$removeIndexerType]);
}