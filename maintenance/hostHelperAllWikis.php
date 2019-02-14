<?php
/**
 * Curse Inc.
 * Dynamic Settings
 * Dynamic Settings Host Helper All Wikis
 *
 * @author 		Alex Smith
 * @copyright	(c) 2014 Curse Inc.
 * @license		GNU General Public License v2.0 or later
 * @package		Dynamic Settings
 * @link		https://gitlab.com/hydrawiki
 *
**/

require_once(dirname(__DIR__, 3).'/maintenance/Maintenance.php');

class hostHelperAllWikis extends Maintenance {
	/**
	 * Run in silent mode.(Suppress Output)
	 *
	 * @var		boolean
	 */
	private $silent = false;

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct() {
		parent::__construct();

		$this->mDescription = "Invokes hostHelper.php for all wikis.";
	}

	/**
	 * Pull all wikis and invoke the same call against all of them.
	 *
	 * @access	public
	 * @return	void
	 */
	public function execute() {
		global $argv;

		array_shift($argv);

		$this->silent = getenv('SILENT');

		//This will update the master wiki.
		$this->runWiki();

		$db = wfGetDB(DB_MASTER);

		$results = $db->select(
			[
				'wiki_sites',
				'wiki_domains'
			],
			[
				'wiki_sites.*',
				'wiki_domains.*'
			],
			[
				'wiki_sites.deleted'	=> 0,
				'wiki_domains.type'		=> \DynamicSettings\Wiki\Domains::getDomainEnvironment()
			],
			__METHOD__,
			null,
			[
				'wiki_domains' => [
					'LEFT JOIN', 'wiki_domains.site_key = wiki_sites.md5_key'
				]
			]
		);

		while ($row = $results->fetchRow()) {
			$this->runWiki($row['domain'], $row);
		}
	}

	/**
	 * Run a Wiki Update
	 *
	 * @param	string	Domain to update
	 * @param	array	Row of wiki information to replace command variables
	 * @return	void	[Outputs to CLI]
	 */
	function runWiki($domain = null, $row = null) {
		global $argv;

		if (!$this->silent) {
			$this->output("\n-----------------------------------\n");
			$this->output(($domain ? $domain : 'Master Wiki'));
			$this->output("\n-----------------------------------\n");
		}
		$cmd = count($argv) ? ' '.@implode(' ', $argv) : null;
		if ($cmd !== null && $row !== null) {
			foreach ($row as $k => $v) {
				$cmd = str_replace('%'.$k.'%', escapeshellcmd($v), $cmd);
			}
		}
		$output = shell_exec('SILENT='.intval($this->silent).' '.PHP_BINDIR.'/php '.($domain ? __DIR__.'/hostHelper.php '.escapeshellcmd($domain) : null).$cmd);
		if (strlen(trim($output)) > 0) {
			$this->output($output);
		}
	}
}

$maintClass = 'hostHelperAllWikis';
require_once(RUN_MAINTENANCE_IF_MAIN);
