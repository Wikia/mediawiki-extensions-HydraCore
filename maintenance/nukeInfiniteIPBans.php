<?php
/**
 * Curse Inc.
 * HydraCore
 * Nuke Infinite IP Bans
 *
 * @author		Alexia E. Smith
 * @copyright	(c) 2014 Curse Inc.
 * @license		GNU General Public License v2.0 or later
 * @link		https://gitlab.com/hydrawiki
 *
 **/

use MediaWiki\MediaWikiServices;

require_once(dirname(__DIR__, 3).'/maintenance/Maintenance.php');

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

		$this->parameters->setDescription( 'Nukes infinite IP bans in the database.');
	}

	/**
	 * Run Maintenance Code
	 *
	 * @access	public
	 * @return	void
	 */
	public function execute() {
		$this->DB = MediaWikiServices::getInstance()
			->getDBLoadBalancer()
			->getMaintenanceConnectionRef( DB_PRIMARY );

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
