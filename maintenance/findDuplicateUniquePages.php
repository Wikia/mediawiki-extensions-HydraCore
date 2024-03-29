<?php
/**
 * Curse Inc.
 * Dynamic Settings
 * Find Duplicate Unique Pages in the Database
 *
 * @author 		Alex Smith
 * @copyright	(c) 2014 Curse Inc.
 * @license		GNU General Public License v2.0 or later
 * @package		Dynamic Settings
 * @link		https://gitlab.com/hydrawiki
 *
**/

use MediaWiki\MediaWikiServices;

require_once(dirname(__DIR__, 3).'/maintenance/Maintenance.php');

class FindDuplicateUniquePages extends Maintenance {
	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct() {
		parent::__construct();

		$this->parameters->setDescription('Finds duplicate unique pages.');
	}

	/**
	 * Pull all wikis and invoke the same call against all of them.
	 *
	 * @access	public
	 * @return	void
	 */
	public function execute() {
		$db = MediaWikiServices::getInstance()
			->getDBLoadBalancer()
			->getMaintenanceConnectionRef( DB_PRIMARY );
		$result = $db->query("SELECT count(CONCAT(page_namespace, '-', page_title)) AS c, CONCAT(page_namespace, '-', page_title) AS n FROM page GROUP BY n HAVING c > 1;");
		while ($row = $result->fetchRow()) {
			list($namespace, $title) = explode('-', $row['n'], 2);
			$pageResult = $db->select(
				['page'],
				['*'],
				[
					'page_title' => $title
				],
				__METHOD__
			);
			$pageRows = [];
			while ($pageRow = $pageResult->fetchRow()) {
				if ($pageRow['page_namespace'] == $namespace) {
					$pageRows[$pageRow['page_id']] = $pageRow;
				}
			}
			$latest = 0;
			$pageIdWinner = 0;
			foreach ($pageRows as $pageId => $pageRow) {
				if ($pageRow['page_latest'] > $latest) {
					$latest = $pageRow['page_latest'];
					$pageIdWinner = $pageId;
				}
			}
			if ($pageIdWinner > 0) {
				unset($pageRows[$pageIdWinner]);
				$nukeKeys = array_keys($pageRows);
				$this->output("Winner for {$title} is {$pageIdWinner}.\n");
				$this->output("Nuking key(s) ".implode(',', $nukeKeys)."\n");
				foreach ($nukeKeys as $nukeKey) {
					if ($nukeKey > 0) {
						$db->delete(
							'page',
							['page_id' => $nukeKey],
							__METHOD__
						);
					}
				}
			}
		}
	}
}

$maintClass = 'FindDuplicateUniquePages';
require_once(RUN_MAINTENANCE_IF_MAIN);
