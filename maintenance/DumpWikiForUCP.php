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

use DynamicSettings\Sites;
use DynamicSettings\Wiki;
use MediaWiki\Shell\Shell;

class DumpWikiForUCP extends Maintenance {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription('Handle setting read-only, waiting for slaves, and dumping the wiki');
		$this->requireExtension('DynamicSettings');
		$this->DIR = dirname(__DIR__) . '/maintenance';
		$this->addOption('domain', 'The wiki to run against', true, true);
		$this->addOption('internal', 'Run in internal test mode', true, true);
	}

	/**
	 * Perform wiki export
	 *
	 * @return void
	 */
	public function execute() {
		$domain = $this->getOption('domain');
		$internal = $this->getOption('internal', false);
		if (!$domain) {
			throw new Exception("No domain found. Set --domain to a valid domain.");
		}
		$wiki = Wiki::loadFromDomain($domain);
		if (!$internal) {
			$this->enableMaintenance($domain);
		}
		// get the DB host name, user and password to be used by mysqldump
		$info = $wiki->getDatabase();
		$command = Shell::command([
			$this->DIR . "/exportDump.sh",
		])
			->params("-h{$info["db_server_replica"]}")
			->params("-u{$info["db_user"]}")
			->params("-p{$info["db_password"]}")
			->params("-d{$info["db_name"]}")
			->limits([
				'time' => 0,
				'memory' => 0,
				'filesize' => 0
			]);

		$result = $command->execute();
		if ($result->getExitCode() !== 0) {
			$this->error($result->getStderr() . "\n");
			throw new Exception("Unable to generate a SQL dump of '{$domain}' (using {$info['db_server']})");
		}
		return true;
	}

	/**
	 * Add read only setting
	 *
	 * @param string $domain
	 *
	 * @return void
	 */
	private function enableMaintenance($domain) {
		$settings = file_get_contents(dirname(__DIR__, 3) . '/sites/' . $domain . '/LocalSettings.php');
		if (strpos($settings, '$wgReadOnly') === false) {
			$settings .= '$wgReadOnly = \'This Wiki is being migrated to UCP.\';' . "\n";
			Sites::writeSiteFile($domain, 'LocalSettings.php', $settings);
		}
		sleep(10);
	}
}

$maintClass = DumpWikiForUCP::class;
require_once RUN_MAINTENANCE_IF_MAIN;
