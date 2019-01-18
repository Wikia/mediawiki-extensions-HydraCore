<?php
/**
 * Curse Inc.
 * HydraCore
 * Nuke Infinite IP Bans
 *
 * @author		Alexia E. Smith
 * @copyright	(c) 2014 Curse Inc.
 * @license		All Rights Reserved
 * @link		http://www.curse.com/
 *
 **/

require_once(dirname(dirname(dirname(__DIR__))).'/maintenance/Maintenance.php');

class nukeInfiniteIPBans extends Maintenance {
	/**
	 * @var Database Connection
	 */
	private $DB;
	/**
	 * Main Constructor
	 *
	 * @access	public
	 */
	public function __construct() {
		parent::__construct();

		$this->mDescription = "Nukes infinite IP bans in the database.";
	}

	/**
	 * Run Maintenance Code
	 *
	 * @access	public
	 * @return	void
	 */
	public function execute() {
		$this->DB = wfGetDB(DB_MASTER);

		$result = $this->DB->select(
			['ipblocks'],
			['count(*) as total'],
			"ipb_expiry = 'infinity' AND ipb_address LIKE '%.%.%.%'",
			__METHOD__
		);

		$total = $result->fetchRow();

		echo "Deleting: ".intval($total['total'])." entries.\n";

		$this->DB->delete(
			'ipblocks',
			"ipb_expiry = 'infinity' AND ipb_address LIKE '%.%.%.%'",
			__METHOD__
		);
	}
}

$maintClass = "nukeInfiniteIPBans";
require_once(RUN_MAINTENANCE_IF_MAIN);
