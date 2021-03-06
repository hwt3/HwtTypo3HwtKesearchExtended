<?php

########################################################################
# Extension Manager/Repository config file for ext "hwt_kesearch_extended".
#
# Auto generated 23-11-2012 14:26
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'HWT ke_search extended',
	'description' => 'Modifications/Improvements for Faceted Search (ke_search)',
	'category' => 'plugin',
	'author' => 'Heiko Westermann',
	'author_email' => 'hwt3@gmx.de',
	'author_company' => 'tie-up media',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '2.5.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.6.0-8.7.99',
			'ke_search' => '2.5.0-2.6.1',
            'php' => '5.3.0-7.1.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);