<?php
/**
 * Fandom Inc.
 * HydraAuth
 * Handle setting read-only, waiting for slaves, and dumping the wiki
 *
 * @package   HydraCore
 * @author    Samuel Hilson
 * @copyright (c) 2020 Fandom Inc.
 * @license   GPL-2.0-or-later
 * @link      https://gitlab.com/hydrawiki
 */

require_once dirname(__DIR__, 3) . '/maintenance/Maintenance.php';

use \DynamicSettings\Wiki;

class DumpWikiInfo extends Maintenance {
	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		$this->addDescription('Echo Wiki Information');
		$this->addOption('domain', 'The wiki to run against', true, true);
	}

	/**
	 * Main Executor
	 *
	 * @access public
	 * @return void
	 */
	public function execute() {
		$domain = $this->getOption('domain');
		if (!$domain) {
			throw new Exception("No domain found. Set --domain to a valid domain.");
		}
		/**
		 * Wiki instance
		 *
		 * @var Wiki
		 */
		$wiki = Wiki::loadFromDomain($domain);
		$dbInfo = $wiki->getDatabase();
		$server = $dbInfo['db_server_replica'];
		$port = $dbInfo['db_port'];
		$user = $dbInfo['db_user'];
		$password = $dbInfo['db_password'];
		$name = $dbInfo['db_name'];
		$params = [
			'sitename' => $wiki->getName(),
			'metaname' => $wiki->getMetaName(),
			'language' => $wiki->getLanguage(),
			'sitekey' => $wiki->getSiteKey(),
			'db_server' => $server,
			'db_port' => $port,
			'db_user' => $user,
			'db_pass' => $password,
			'db_name' => $name

		];
		echo json_encode($params) . "\n";
	}
}

$maintClass = DumpWikiInfo::class;
require_once RUN_MAINTENANCE_IF_MAIN;
