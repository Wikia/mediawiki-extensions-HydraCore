<?php
/**
 * Fandom Inc.
 * HydraAuth
 * Handle setting read-only
 *
 * @package   HydraCore
 * @author    Samuel Hilson
 * @copyright (c) 2020 Fandom Inc.
 * @license   GPL-2.0-or-later
 * @link      https://gitlab.com/hydrawiki
 */

require_once dirname(__DIR__, 3) . '/maintenance/Maintenance.php';

use DynamicSettings\Sites;

class DumpWikiEnableMaintenance extends Maintenance {
	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription('Handle setting read-only');
		$this->requireExtension('DynamicSettings');
		$this->DIR = dirname(__DIR__) . '/maintenance';
		$this->addOption('domain', 'The wiki to run against', true, true);
	}

	/**
	 * Perform wiki export
	 *
	 * @return void
	 */
	public function execute() {
		$domain = $this->getOption('domain');
		if (!$domain) {
			throw new Exception("No domain found. Set --domain to a valid domain.");
		}

		$this->enableMaintenance($domain);

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

$maintClass = DumpWikiEnableMaintenance::class;
require_once RUN_MAINTENANCE_IF_MAIN;
