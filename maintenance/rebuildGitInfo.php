<?php
/**
 * Rebuilt Git Info Maintenance Script
 *
 * @author 		Alexia E. Smith
 * @copyright	(c) 2018 Curse Media
 * @license		All Rights Reserved
 * @link		https://www.curse.com/
**/

require_once(dirname(__DIR__, 3).'/maintenance/Maintenance.php');

class RebuildGitInfo extends Maintenance {
	/**
	 * Main Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct() {
		parent::__construct();

		$this->addDescription('Write out gitinfo.json.');
	}

	/**
	 * Main Executor
	 *
	 * @access	public
	 * @return	void
	 */
	public function execute() {
		global $IP;

		$gitInfo = new GitInfo($IP, false);
		$gitInfo->precomputeValues();

		$path = realpath($IP).'/gitinfo.json';
		$jsonRaw = file_get_contents($path);
		$json = json_decode($jsonRaw, true);
		if (isset($json['remoteURL'])) {
			$json['remoteURL'] = '';
			file_put_contents($path, json_encode($json));
		}
	}
}

$maintClass = RebuildGitInfo::class;
require_once RUN_MAINTENANCE_IF_MAIN;
