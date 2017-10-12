<?php

namespace Hwt\HwtKesearchExtended\Xclass;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class BackendModuleController extends \TeaminmediasPluswerk\KeSearch\Controller\BackendModuleController {

    /*
     * function renderIndexTableInformation
     *
     * HWT: Changed presentation of records
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

                // Changed by HWT: even / odd classes
                $content .= '
                    <table class="t3-table statistics">
                        <tr class="even">
                            <td class="infolabel">Records total: </td>
                            <td>'.$row['Rows'].'</td>
                        </tr>
                        <tr class="odd">
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
                        <tr class="even">
                            <td class="infolabel">Data size: </td>
                            <td>'.$dataLength.'</td>
                        </tr>
                        <tr class="odd">
                            <td class="infolabel">Index size: </td>
                            <td>'.$indexLength.'</td>
                        </tr>
                        <tr class="even">
                            <td class="infolabel">Complete table size: </td>
                            <td>'.$completeLength.'</td>
                        </tr>';
            }
        }
        $content .= '</table>';

        return $content;
    }



    /**
     * Start index action
     *
     * HWT: Changed button output format
     *
     * @return	void
     */
    public function startIndexingAction()
    {
        // make indexer instance and init
        /* @var $indexer tx_kesearch_indexer */
        $indexer = GeneralUtility::makeInstance('tx_kesearch_indexer');

        // get indexer configurations
        $indexerConfigurations = $indexer->getConfigurations();

        $content = '';

        // action: start indexer or remove lock
        if ($this->do == 'startindexer') {
            // start indexing in verbose mode with cleanup process
            $content .= $indexer->startIndexing(true, $this->extConf);
        } else {
            if ($this->do == 'rmLock') {
                // remove lock from registry - admin only!
                if ($this->getBackendUser()->user['admin']) {
                    $this->registry->removeAllByNamespace('tx_kesearch');
                } else {
                    $content .=
                        '<p>'
                        . LocalizationUtility::translate(
                            'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:not_allowed_remove_indexer_lock',
                            'KeSearch'
                        )
                        . '</p>';
                }
            }
        }


        // check for index process lock in registry
        // remove lock if older than 12 hours
        $lockTime = $this->registry->get('tx_kesearch', 'startTimeOfIndexer');
        $compareTime = time() - (60 * 60 * 12);
        if ($lockTime !== null && $lockTime < $compareTime) {
            // lock is older than 12 hours
            // remove lock and show "start index" button
            $this->registry->removeAllByNamespace('tx_kesearch');
            $lockTime = null;
        }

        // show information about indexer configurations and number of records
        // if action "start indexing" is not selected
        if ($this->do != 'startindexer') {
            $content .= $this->printNumberOfRecords();
            $content .= $this->printIndexerConfigurations($indexerConfigurations);
        }

        // show "start indexing" or "remove lock" button
        if ($lockTime !== null) {
            if (!$this->getBackendUser()->user['admin']) {
                // print warning message for non-admins
                $content .= '<br /><p style="color: red; font-weight: bold;">WARNING!</p>';
                $content .= '<p>The indexer is already running and can not be started twice.</p>';
            } else {
                // show 'remove lock' button for admins
                $content .= '<br /><p>The indexer is already running and can not be started twice.</p>';
                $content .= '<p>The indexing process was started at ' . strftime('%c', $lockTime) . '.</p>';
                $content .= '<p>You can remove the lock by clicking the following button.</p>';
                $moduleUrl =
                    BackendUtility::getModuleUrl(
                        'web_KeSearchBackendModule',
                        array('id' => $this->id, 'do' => 'rmLock')
                    );
                $content .= '<br /><a class="lock-button" href="' . $moduleUrl . '">RemoveLock</a>';
            }
        } else {
            // no lock set - show "start indexer" link if indexer configurations have been found
            if ($indexerConfigurations) {
                $moduleUrl =
                    BackendUtility::getModuleUrl(
                        'web_KeSearchBackendModule',
                        array('id' => $this->id, 'do' => 'startindexer')
                    );

                // Changed by HWT
                $content .= '<br /><a class="t3-button btn btn-info" href="' . $moduleUrl . '">'
                    .
                    LocalizationUtility::translate(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:start_indexer',
                        'KeSearch'
                    )
                    . '</a>';
            } else {
                $content .=
                    '<div class="alert alert-info">'
                    .
                    LocalizationUtility::translate(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:no_indexer_configurations',
                        'KeSearch'
                    )
                    . '</div>';
            }
        }

        $this->view->assign('content', $content);
    }



    /**
     * Clear search index action
     *
     * HWT: Changed button output format
     *
     * @return	void
     */
    public function clearSearchIndexAction() {
        $content = '';
        // admin only access
        if ($this->getBackendUser()->user['admin']) {
            if ($this->do == 'clear') {
                $query = 'TRUNCATE TABLE tx_kesearch_index';
                $res = $this->databaseConnection->sql_query($query);
            }
            $content .= '<p>'
                .
                LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:index_contains',
                    'KeSearch'
                )
                . ' '
                . $this->getNumberOfRecordsInIndex()
                . ' '
                . LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:records',
                    'KeSearch'
                )
                . '.</p>';
            // show "clear index" link
            $moduleUrl = BackendUtility::getModuleUrl(
                'web_KeSearchBackendModule',
                array('id' => $this->id, 'do' => 'clear')
            );
            // Changed by HWT
            $content .= '<br /><a class="t3-button btn btn-danger" href="' . $moduleUrl . '"><span class="fa-stack fa-lg callout-icon"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-info fa-stack-1x" style="color:#a32e2e;"></i></span> Clear whole search index!</a>';
        } else {
            $content .= '<p>Clear search index: This function is available to admins only.</p>';
        }
        $this->view->assign('content', $content);
    }

}