<?php
/**
 * Curse Inc.
 * Hydra Core
 * Fix missing revision_text
 *
 * @copyright (c) 2019 Curse Inc.
 * @license   GPL-2.0-or-later
 * @package   Hydra Core
 * @link      https://gitlab.com/hydrawiki
 */

use MediaWiki\MediaWikiServices;

require_once dirname(__DIR__, 3) . '/maintenance/Maintenance.php';
class FixMissingRevisions extends Maintenance {
	/**
	 * Main Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->parameters->setDescription( 'Fix rev_text_id pointing to non-existent entries' );
	}

	/**
	 * Find and fix missing revision text.
	 *
	 * @access public
	 * @return void
	 */
	public function execute() {
		$loadBalancer = MediaWikiServices::getInstance()
			->getDBLoadBalancer();
		$dbw = $loadBalancer->getMaintenanceConnectionRef( DB_PRIMARY );
		$db = $loadBalancer->getMaintenanceConnectionRef( DB_REPLICA );
		$results = $db->select(['revision'], ['rev_id', 'rev_text_id'], ['rev_text_id not in (select text.old_id from text)'], __METHOD__);

		while ($row = $results->fetchRow()) {
			$oldID = $row['rev_text_id'];
			$this->output('Fixing revision ' . $row['rev_id'] . ', which was missing rev_text_id: ' . $row['rev_text_id'] . "\n");
			$dbw->insert('text', ['old_id' => $row['rev_text_id'], 'old_text' => '', 'old_flags' => 'utf-8'], __METHOD__, ["IGNORE"]);
		}
	}
}
$maintClass = "FixMissingRevisions";
require_once RUN_MAINTENANCE_IF_MAIN;
