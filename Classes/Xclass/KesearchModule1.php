<?php

class Tx_HwtKesearchExtended_Xclass_KesearchModule1 extends tx_kesearch_module1 {
/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($GLOBALS['BE_USER']->user['admin'] && !$this->id))	{

				// Draw the header.
			if (TYPO3_VERSION_INTEGER >= 6002000) {
				$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('template');
			} else {
				$this->doc = t3lib_div::makeInstance('mediumDoc');
			}
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
			$this->doc->inDocStyles = '
				/*table {font-size:inherit;}*/

				.clearer {
					line-height: 1px;
					height: 1px;
					clear: both;
					display:block;
				}

				.box {
					margin-top: 10px;
				}
				.box .headline {
					border: 1px solid #666;
					padding: 1px 1px 1px 5px;
					background: #888;
					color: white;
					margin-bottom: 3px;
				}
				.box .content {
					border: 1px solid #666;
					padding: 1px 1px 1px 5px;
					background: white;
				}
			';

			$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
			$this->content .= '<div id="typo3-docheader"><div class="typo3-docheader-functions">';
			$this->content .= t3lib_BEfunc::getFuncMenu($this->id,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']);
			$this->content .= '</div></div>';
			$this->content .= '<div id="typo3-docbody"><div id="typo3-inner-docbody">';
			$this->content .= $this->doc->header($GLOBALS['LANG']->getLL('title'));

			// Render content:
			$this->moduleContent();

			// ShortCut
			if ($BE_USER->mayMakeShortcut())	{
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero

			if (TYPO3_VERSION_INTEGER >= 6002000) {
				$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('mediumDoc');
			} else {
				$this->doc = t3lib_div::makeInstance('mediumDoc');
			}
			$this->doc->backPath = $BACK_PATH;
			$this->content.= $GLOBALS['LANG']->getLL('select_a_page');
			$this->content.=$this->doc->spacer(10);
		}
			$this->content.='</div></div>';

	}


	/**
	 * Generates the module content
	 *
	 * @return	void
	 */
	function moduleContent()	{

		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ke_search']);

		switch((string)$this->MOD_SETTINGS['function'])	{

			// start indexing process
			case 1:
				$content = '';
				if (TYPO3_VERSION_INTEGER >= 6002000) {
					$this->registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_Registry');
				} else {
					$this->registry = t3lib_div::makeInstance('t3lib_Registry');
				}

				if (t3lib_div::_GET('do') == 'startindexer') {
					// make indexer instance and init
					if (TYPO3_VERSION_INTEGER >= 6002000) {
						$indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_indexer');
					} else {
						$indexer = t3lib_div::makeInstance('tx_kesearch_indexer');
					}
					$cleanup = $this->extConf['cleanupInterval'];
					$content .= $indexer->startIndexing(true, $this->extConf); // start indexing in verbose mode with cleanup process
				} else if (t3lib_div::_GET('do') == 'rmLock') {
					// remove lock from registry - admin only!
					if ($GLOBALS['BE_USER']->user['admin']) {
						$this->registry->removeAllByNamespace('tx_kesearch');
					} else {
						$content .= '<p>' . $GLOBALS['LANG']->getLL('not_allowed_remove_indexer_lock') . '</p>';
					}
				}

				// check for index process lock in registry
				$lockTime = $this->registry->get('tx_kesearch', 'startTimeOfIndexer');
				if ($lockTime !== null) {
					// lock is set
					$compareTime = time() - (60*60*12);
					if ($lockTime < $compareTime) {
						// lock is older than 12 hours
						// remove lock and show "start index" button
						$this->registry->removeAllByNamespace('tx_kesearch');
						if (TYPO3_VERSION_INTEGER < 6002000) {
							$content .= '<br /><a class="t3-button" href="mod.php?id='.$this->id.'&M=web_txkesearchM1&do=startindexer">' . $GLOBALS['LANG']->getLL('start_indexer') . '</a>';
						} else {
							$moduleUrl = TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txkesearchM1', array('id' => $this->id, 'do' => 'startindexer'));
							$content .= '<br /><a class="t3-button" href="' . $moduleUrl . '">' . $GLOBALS['LANG']->getLL('start_indexer') . '</a>';
						}
					} else {
						// lock is not older than 12 hours
						if (!$GLOBALS['BE_USER']->user['admin']) {
							// print warning message for non-admins
							$content .= '<br /><p style="color: red; font-weight: bold;">WARNING!</p>';
							$content .= '<p>The indexer is already running and can not be started twice.</p>';
						} else {
							// show 'remove lock' button for admins
							$content .= '<br /><p>The indexer is already running and can not be started twice.</p>';
							$content .= '<p>The indexing process was started at '.strftime('%c', $lockTime).'.</p>';
							$content .= '<p>You can remove the lock by clicking the following button.</p>';
							if (TYPO3_VERSION_INTEGER < 6002000) {
								$content .= '<br /><a class="lock-button" href="mod.php?id='.$this->id.'&M=web_txkesearchM1&do=rmLock">RemoveLock</a>';
							} else{
								$moduleUrl = TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txkesearchM1', array('id' => $this->id, 'do' => 'rmLock'));
								$content .= '<br /><a class="lock-button" href="' . $moduleUrl . '">RemoveLock</a>';
							}
						}
					}
				} else {
					// no lock set - show "start indexer" link
					if (TYPO3_VERSION_INTEGER < 6002000) {
						$content .= '<br /><a class="t3-button" href="mod.php?id='.$this->id.'&M=web_txkesearchM1&do=startindexer">' . $GLOBALS['LANG']->getLL('start_indexer') . '</a>';
					} else {
						$moduleUrl = TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txkesearchM1', array('id' => $this->id, 'do' => 'startindexer'));
						$content .= '<br /><a class="t3-button" href="' . $moduleUrl . '">' . $GLOBALS['LANG']->getLL('start_indexer') . '</a>';
					}
				}

				$this->content.=$this->doc->section('Start Indexer',$content,0,1);
			break;


			// show indexed content
			case 2:
				if ($this->id) {

					if (t3lib_div::_GET('do') == 'reindex') {
						if (TYPO3_VERSION_INTEGER >= 6002000) {
							$indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_indexer');
						} else {
							$indexer = t3lib_div::makeInstance('tx_kesearch_indexer');
						}
						$cleanup = $this->extConf['cleanupInterval'];
						$content = $indexer->startIndexing(true, $this->extConf); // start indexing in verbose mode with cleanup process
					}

					// page is selected: get indexed content
					$content = '<h2>Index content for page '.$this->id.'</h2>';
					$content .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_cs($this->pageinfo['_thePath'],-50);
					$content .= $this->getIndexedContent($this->id);
				} else {
					// no page selected: show message
					$content = 'Select page first';
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
					$table = 'tx_kesearch_index';

					if (t3lib_div::_GET('do') == 'clear') {
						$query = 'TRUNCATE TABLE ' . $table;
						$res = $GLOBALS['TYPO3_DB']->sql_query($query);
					}

					$query = 'SELECT COUNT(*) AS number_of_records FROM ' . $table;
					$res = $GLOBALS['TYPO3_DB']->sql_query($query);
					$row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					$content .= '<p>Search index table contains ' . $row['number_of_records'] . ' records.</p>';

					// show "clear index" link
					if (TYPO3_VERSION_INTEGER < 6002000) {
						$content .= '<br /><a class="t3-button" href="mod.php?id='.$this->id.'&M=web_txkesearchM1&do=clear"><span class="t3-icon t3-icon-actions t3-icon-dialog-error">&nbsp;</span> Clear whole search index!</a>';
					} else {
						$moduleUrl = TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('web_txkesearchM1', array('id' => $this->id, 'do' => 'clear'));
						$content .= '<br /><a class="t3-button" href="' . $moduleUrl . '"><span class="t3-icon t3-icon-actions t3-icon-dialog-error">&nbsp;</span> Clear whole search index!</a>';
					}
				} else {
					$content .= '<p>Clear search index: This function is available to admins only.</p>';
				}


				$this->content.=$this->doc->section('Clear Index',$content,0,1);

				break;


		}
	}


	/*
	 * function renderIndexTableInformation
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
					<table class="t3-table">
						<tbody>
						<tr>
							<td class="label">Records total: </td>
							<td>'.$row['Rows'].'</td>
						</tr>
						<tr>
							<td class="label">Records by indexer: </td>
							<td>';
                
                
                $query = 'SELECT type,COUNT(*) FROM ' . $table . ' GROUP BY type';
                $countRes = $GLOBALS['TYPO3_DB']->sql_query($query);
                
                while ($countRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($countRes)) {
                    //$content .= $countRow . '<br />';
                    $content .= $countRow['type'] . ': ' . $countRow['COUNT(*)'] . '<br />';
                }
                
                    
                $content .= '</td>
						</tr>
						<tr>
							<td class="label">Data size: </td>
							<td>'.$dataLength.'</td>
						</tr>
						<tr>
							<td class="label">Index size: </td>
							<td>'.$indexLength.'</td>
						</tr>
						<tr>
							<td class="label">Complete table size: </td>
							<td>'.$completeLength.'</td>
						</tr>
						</tbody>
					</table>';
			}
		}


		return $content;
	}


	/*
	 * function getIndexedContent
	 * @param $pageUid page uid
	 */
	function getIndexedContent($pageUid) {

		$fields = '*';
		$table = 'tx_kesearch_index';
		$where = '(type="page" AND targetpid="'.intval($pageUid).'")  ';
		$where .= 'OR (type<>"page" AND pid="'.intval($pageUid).'")  ';
		$where .= t3lib_befunc::BEenableFields($table,$inv=0);
		$where .= t3lib_befunc::deleteClause($table,$inv=0);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
		// t3lib_div::debug($GLOBALS['TYPO3_DB']->SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit=''),1);
		// $anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			// build type image path
			switch($row['type']) {
				case 'page':
					$imagePath = t3lib_extMgm::extRelPath('ke_search').'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_0.gif';
					break;
				case 'ke_yac':
					$imagePath = t3lib_extMgm::extRelPath('ke_search').'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_1.gif';
					break;
				default:
					$imagePath = t3lib_extMgm::extRelPath('ke_search').'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_2.gif';
					break;

			}


			// build tag table
			$tagTable = '<div class="tags" >';
			$cols = 3;
			$tags = t3lib_div::trimExplode(',', $row['tags'], true);
			$i=1;
			foreach ($tags as $tag) {
				$tagTable .= '<span class="tag">' . $tag . '</span>';
			}
			$tagTable .= '</div>';

			// build content
			$timeformat = '%d.%m.%Y %H:%M';
			$content .= '
				<table class="t3-table"><thead><tr><td colspan="2">'
				. '<img src="'.$imagePath.'" border="0" style="float:right;">'
				. '<span class="title">'.$row['title'].'</span>'
				.  '</td></tr></thead><tbody>'
				. $this->renderFurtherInformation('Type', $row['type'])
				. $this->renderFurtherInformation('Words', str_word_count($row['content']))
				. $this->renderFurtherInformation('Language', $row['language'])
				. $this->renderFurtherInformation('Created', strftime($timeformat, $row['crdate']))
				. $this->renderFurtherInformation('Modified', strftime($timeformat, $row['tstamp']))
				. $this->renderFurtherInformation('Sortdate', ($row['sortdate'] ? strftime($timeformat, $row['sortdate']) : ''))
				. $this->renderFurtherInformation('Starttime', ($row['starttime'] ? strftime($timeformat, $row['starttime']) : ''))
				. $this->renderFurtherInformation('Endtime', ($row['endtime'] ? strftime($timeformat, $row['endtime']) : ''))
				. $this->renderFurtherInformation('FE Group', $row['fe_group'])
				. $this->renderFurtherInformation('Target Page', $row['targetpid'])
				. $this->renderFurtherInformation('URL Params', $row['params'])
				. $this->renderFurtherInformation('Original PID', $row['orig_pid'])
				. $this->renderFurtherInformation('Original UID', $row['orig_uid'])
				. $this->renderFurtherInformation('Path', $row['directory'])
				. '<tr><td colspan="2">'
				. '<div class="box"><div class="headline">Abstract</div><div class="content">' . nl2br($row['abstract']) .'</div></div>'
				. '<div class="box"><div class="headline">Content</div><div class="content">' . nl2br($row['content']) .'</div></div>'
				.  '<div class="box"><div class="headline">Tags</div><div class="content">'.$tagTable.'</div></div>'
				. '</td></tr></tbody></table>';

		}

		return $content;

	}
	
	/**
	 *
	 * @param string $label
	 * @param string $content
	 * @return string
	 */
	function renderFurtherInformation($label, $content) {
		return '<tr><td>' . $label . ': </td><td>' . $content . '</td></tr>';
	}


	/**
	 *
	 * @param string $table
	 * @param integer $language
	 * @param integer $timestampStart
	 * @param string $pidWhere
	 * @param string $tableCol
	 * @return string
	 */
	public function getAndRenderStatisticTable($table, $language, $timestampStart, $pidWhere, $tableCol) {
		$content = '<div style="width=50%; float:left; margin-right:1em;">';
		$content .= '<h2 style="margin:0em;">' . $tableCol . 's</h2>';

		$rows = '';

		// get statistic data from db
		$fields = 'count('. $tableCol . ') as num, ' . $tableCol;
		$where = 'tstamp > ' . $timestampStart . ' AND language=' . $language . ' ' . $pidWhere;
		$groupBy = $tableCol . ' HAVING count(' . $tableCol . ')>0';
		$orderBy = 'num desc';
		$limit = '';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where, $groupBy, $orderBy, $limit);
		$numResults = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

		// get statistic
		$i=1;
		if ($numResults) {
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$cssClass = ($i%2==0) ?  'even' : 'odd';
				$rows .= '<tr>';
				$rows .= '	<td class="'.$cssClass.'">'.$row[$tableCol].'</td>';
				$rows .= '	<td class="times '.$cssClass.'">'.$row['num'].'</td>';
				$rows .= '</tr>';
				$i++;
			}

			$content .=
				'<table class="t3-table">
					<tr>
					<th>' . $tableCol . '</th>
					<th>counter</th>
					</tr>'
				.$rows.
				'</table>';
		}

		$content .= '</div>';

		return $content;
	}
}