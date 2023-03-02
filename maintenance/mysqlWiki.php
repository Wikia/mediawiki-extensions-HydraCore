#!/usr/bin/env php
<?php
/**
 * Maintenance script to aid in connecting to a wiki database
 *
 * @author    Robert Nix
 * @copyright (c) 2019 Curse Inc.
 * @license   GPL-2.0-or-later
 * @link      https://gitlab.com/hydrawiki
 **/

require_once dirname(__DIR__, 3) . '/maintenance/Maintenance.php';

use \DynamicSettings\Wiki;

//todo this script probably has to be removed, as it has dependency with not included https://github.com/Wikia/hydra/tree/develop/extensions/DynamicSettings
class MysqlWiki extends Maintenance {
	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		$this->addDescription('Echo database login command');
		$this->addArg('domain', 'Wiki domain name', false);
	}

	/**
	 * Main Executor
	 *
	 * @access public
	 * @return void
	 */
	public function execute() {
		global $wgDBuser, $wgDBpassword, $wgDBname, $wgDBserver;

		$domain = $this->getArg(0);
		if ($domain == null) {
			if (strpos($wgDBserver, ':') !== false) {
				list($host, $port) = explode(':', $wgDBserver);
				echo "mysql -h {$host} -P {$port} -u {$wgDBuser} -p{$wgDBpassword} {$wgDBname}";
			} else {
				echo "mysql -h {$wgDBserver} -u {$wgDBuser} -p{$wgDBpassword} {$wgDBname}";
			}
			return;
		}

		$wiki = Wiki::loadFromDomain($domain);
		if ($wiki == false) {
			return;
		}

		$dbInfo = $wiki->getDatabase();
		$server = $dbInfo['db_server'];
		$port = $dbInfo['db_port'];
		$user = $dbInfo['db_user'];
		$password = $dbInfo['db_password'];
		$name = $dbInfo['db_name'];
		echo "mysql -h {$server} -P {$port} -u {$user} -p{$password} {$name}";
	}
}

$maintClass = MysqlWiki::class;
require_once RUN_MAINTENANCE_IF_MAIN;
