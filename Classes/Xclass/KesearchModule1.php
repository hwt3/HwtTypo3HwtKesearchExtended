<?php
//namespace Hwt\HwtKesearchExtended\Xclass;

class Tx_HwtKesearchExtended_Xclass_KesearchModule1 extends tx_kesearch_module1 {
	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
     *
     * HWT: Changed css embedding
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = \TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($this->id,$this->perms_clause);

		$access = is_array($this->pageinfo) ? 1 : 0;

		// create document template
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');

		if (($this->id && $access) || ($GLOBALS['BE_USER']->user['admin'] && !$this->id)) {

			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="post" enctype="multipart/form-data">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)	{
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			// add some css
			$cssFile = 'Resources/Public/Backend/module.css';
			$this->doc->getPageRenderer()->addCssFile(TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('hwt_kesearch_extended') . $cssFile);

			$this->content .= '<div id="typo3-docheader"><div class="typo3-docheader-functions">';

			$this->content .= \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']);
			$this->content .= '</div></div>';

			$this->content .= '<div id="typo3-docbody"><div id="typo3-inner-docbody">';
			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));

			// Render content:
			$this->moduleContent();

			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content .= $this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content .= $this->doc->spacer(10);
		} else {
			$this->doc->backPath = $BACK_PATH;
			$this->content .= '<div class="alert alert-info">' .  $GLOBALS['LANG']->getLL('select_a_page'). '</div>';
			$this->content .= $this->doc->spacer(10);
		}

		$this->content.='</div></div>';
	}

	/*
	 * function renderIndexTableInformation
     *
     * HWT: Changed presentation of records by indexer type and added t3-table class
	 */
	function renderIndexTableInformation() {

		$table = 'tx_kesearch_index';

		// get table status
		$query = 'SHOW TABLE STATUS';
		$res = $GLOBALS['TYPO3_DB']->sql_query($query);
		while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($row['Name'] == $table) {

				$dataLength = $this->formatFilesize($row['Data_length']);
				$indexLength = $this->formatFilesize($row['Index_length']);
				$completeLength = $this->formatFilesize($row['Data_length'] + $row['Index_length']);

				$content .= '
					<table class="t3-table statistics">
						<tr>
							<td class="infolabel">Records total: </td>
							<td>'.$row['Rows'].'</td>
						</tr>
                        <tr>
                            <td class="infolabel">Records by indexer: </td>
							<td>';
                
                $results_per_type = $this->getNumberOfRecordsInIndexPerType();
                if (count($results_per_type)) {
                    foreach ($results_per_type as $type => $count) {
                        $content .= $type . ': ' . $count . '<br />';
                    }
                }
                
                $content .= '</td>
                        </tr>
						<tr>
							<td class="infolabel">Data size: </td>
							<td>'.$dataLength.'</td>
						</tr>
						<tr>
							<td class="infolabel">Index size: </td>
							<td>'.$indexLength.'</td>
						</tr>
						<tr>
							<td class="infolabel">Complete table size: </td>
							<td>'.$completeLength.'</td>
						</tr>';
			}
		}
		$content .= '</table>';

		return $content;
	}



	/**
	 * Generates the module content
     *
     * HWT: Changed button output format
	 *
	 * @return	void
	 */
	function moduleContent()	{
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ke_search']);
		$content = '';

		$do = TYPO3\CMS\Core\Utility\GeneralUtility::_GET('do');

		switch((string)$this->MOD_SETTINGS['function'])	{

			// start indexing process
			case 1:
				// make indexer instance and init
				/* @var $indexer tx_kesearch_indexer */
				$indexer = TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_indexer');

				// get indexer configurations
				$indexerConfigurations = $indexer->getConfigurations();

				// action: start indexer or remove lock
				if ($do == 'startindexer') {
					// start indexing in verbose mode with cleanup process
					$content .= $indexer->startIndexing(true, $this->extConf);
				} else if ($do == 'rmLock') {
					// remove lock from registry - admin only!
					if ($GLOBALS['BE_USER']->user['admin']) {
						$this->registry->removeAllByNamespace('tx_kesearch');
					} else {
						$content .= '<p>' . $GLOBALS['LANG']->getLL('not_allowed_remove_indexer_lock') . '</p>';
					}
				}

				// show information about indexer configurations and number of records
				// if action "start indexing" is not selected
				if ($do != 'startindexer') {
					$content .= $this->printIndexerConfigurations($indexerConfigurations);
					$content .= $this->printNumberOfRecords();
				}

				// check for index process lock in registry
				// remove lock if older than 12 hours
				$lockTime = $this->registry->get('tx_kesearch', 'startTimeOfIndexer');
				$compareTime = time() - (60*60*12);
				if ($lockTime !== null && $lockTime < $compareTime) {
						// lock is older than 12 hours
						// remove lock and show "start index" button
						$this->registry->removeAllByNamespace('tx_kesearch');
						$lockTime = null;
				}

				// show "start indexing" or "remove lock" button
				if ($lockTime !== null) {
					if (!$GLOBALS['BE_USER']->user['admin']) {
						// print warning message for non-admins
						$content .= '<br /><p style="color: red; font-weight: bold;">WARNING!</p>';
						$content .= '<p>The indexer is already running and can not be started twice.</p>';
					} else {
						// show 'remove lock' button for admins
						$content .= '<br /><p>The indexer is already running and can not be started twice.</p>';
						$content .= '<p>The indexing process was started at '.strftime('%c', $lockTime).'.</p>';
						$content .= '<p>You can remove the lock by clicking the following button.</p>';
						$moduleUrl = TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txkesearchM1', array('id' => $this->id, 'do' => 'rmLock'));
						$content .= '<br /><a class="lock-button" href="' . $moduleUrl . '">RemoveLock</a>';
					}
				} else {
					// no lock set - show "start indexer" link if indexer configurations have been found
					if ($indexerConfigurations) {
						$moduleUrl = TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txkesearchM1', array('id' => $this->id, 'do' => 'startindexer'));
						$content .= '<br /><a class="t3-button btn btn-info" href="' . $moduleUrl . '">' . $GLOBALS['LANG']->getLL('start_indexer') . '</a>';
					} else {
						$content .= '<div class="alert alert-info">' . $GLOBALS['LANG']->getLL('no_indexer_configurations') . '</div>';
					}
				}

				$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('start_indexer'), $content, 0, 1);
			break;


			// show indexed content
			case 2:
				if ($this->id) {

					// page is selected: get indexed content
					$content = '<h2>Index content for page '.$this->id.'</h2>';
					$content .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.  TYPO3\CMS\Core\Utility\GeneralUtility::fixed_lgd_cs($this->pageinfo['_thePath'],-50);
					$content .= $this->getIndexedContent($this->id);
				} else {
					// no page selected: show message
					$content = '<div class="alert alert-info">' .  $GLOBALS['LANG']->getLL('select_a_page'). '</div>';
				}

				$this->content.=$this->doc->section('Show Indexed Content',$content,0,1);
				break;


			// index table information
			case 3:

				$content = $this->renderIndexTableInformation();
				$this->content.=$this->doc->section('Index Table Information',$content,0,1);

				break;

			// searchword statistics
			case 4:

				// days to show
				$days = 30;
				$content = $this->getSearchwordStatistics($this->id, $days);
				$this->content.=$this->doc->section('Searchword Statistics for the last ' . $days . ' days', $content, 0, 1);

				break;

			// clear index
			case 5:
				$content = '';

					// admin only access
				if ($GLOBALS['BE_USER']->user['admin'])	{

					if ($do == 'clear') {
						$query = 'TRUNCATE TABLE tx_kesearch_index' . $table;
						$res = $GLOBALS['TYPO3_DB']->sql_query($query);
					}

					$content .= '<p>' . $GLOBALS['LANG']->getLL('index_contains') . ' ' . $this->getNumberOfRecordsInIndex() . ' ' . $GLOBALS['LANG']->getLL('records') . '.</p>';

					// show "clear index" link
					$moduleUrl = TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txkesearchM1', array('id' => $this->id, 'do' => 'clear'));
					$content .= '<br /><a class="t3-button btn btn-danger" href="' . $moduleUrl . '"><span class="t3-icon t3-icon-actions t3-icon-dialog-error">&nbsp;</span> Clear whole search index!</a>';
				} else {
					$content .= '<p>Clear search index: This function is available to admins only.</p>';
				}


				$this->content.=$this->doc->section('Clear Index',$content,0,1);

				break;

			// last indexing report
			case 6:
				$content = $this->showLastIndexingReport();
				$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('function6'), $content, 0, 1);
				break;
		}
	}
}