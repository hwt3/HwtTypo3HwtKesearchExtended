<?php

class Tx_HwtKesearchExtended_Xclass_KesearchModule1 extends tx_kesearch_module1 {
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
					<table class="statistics">
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
					</table>';
			}
		}


		return $content;
	}
}