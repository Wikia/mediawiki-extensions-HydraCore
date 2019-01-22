<?php
/**
 * Hard Core Search Index Fixing.
 *
 * @author 		Alexia E. Smith
 * @copyright	(c) 2017 Curse Inc.
 * @license		All Rights Reserved
 * @link		http://www.curse.com/
 *
**/

require_once(dirname(__DIR__, 3).'/maintenance/Maintenance.php');

class fixSearchIndex extends Maintenance {
	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct() {
		parent::__construct();

		$this->mDescription = "Fix the search index EVEN IF IT ERRORS.";
	}

	/**
	 * Fix it.
	 *
	 * @access	public
	 * @return	void
	 */
	public function execute() {
		global $argv;

		array_shift($argv);

		$domain = $this->getArg(0);

		if (!isset($domain)) {
			$this->error("Please specify a domain.\n", 1);
		}

		$wiki = \DynamicSettings\Wiki::loadFromDomain($domain);
		if ($wiki === false || empty($wiki->getSiteKey())) {
			$this->error("Could not load wiki from DynamicSettings.\n", 1);
		}

		$isGood = false;
		$forceIndex = false;
		$extra = '';
		while ($isGood === false) {
			$output = $this->runAndGetOutput($domain, $extra);
			$extra = ''; //Reset after running.
			$this->output($output);

			if (strpos($output, '--reindexAndRemoveOk --indexIdentifier=now') !== false) {
				$this->output("-------------------------------\nREINDEX AND REMOVE!\n-------------------------------\n");
				$extra = '--reindexAndRemoveOk --indexIdentifier=now';
				continue;
			}

			if (strpos($output, 'script with --startOver and') !== false || strpos($output, 'Blowing away index to start over...⧼index') !== false || strpos($output, 'Number of shards is incorrect and cannot be changed without a rebuild') !== false) {
				$this->output("-------------------------------\nSTART OVER!\n-------------------------------\n");
				$extra = '--startOver';
				$forceIndex = true;
				continue;
			}

			if (strpos($output, 'but the one of them currently active. Here is the list') !== false) {
				$this->output("-------------------------------\nGoing down to NUKE TOWN!\n-------------------------------\n");
				preg_match('#but the one of them currently active. Here is the list: ([a-zA-Z_\-0-9,]+?)$#i', $output, $matches);
				$indexes = trim($matches[1]);
				if (empty($indexes)) {
					$this->error("Indexes to delete could not be parsed!", 1);
				}
				$deleteOutput = shell_exec('curl -s -XDELETE '.escapeshellarg('http://'.$wiki->getSearchSetup()['search_server'].':'.$wiki->getSearchSetup()['search_port'].'/'.$indexes));
				$json = @json_decode($deleteOutput, true);
				if (!isset($json['acknowledged']) || $json['acknowledged'] !== true) {
					$this->output("Could not delete indexes from Elasticsearch!\n".$deleteOutput."\n");
					$this->output("-------------------------------\nSTART OVER!\n-------------------------------\n");
					$extra = '--startOver';
				}
				$forceIndex = true;
				continue;
			}

			if (empty($extra)) {
				$isGood = true;
				break;
			}
		}
		if ($forceIndex) {
			$this->output("-------------------------------\nFORCE SEARCH INDEX!\n-------------------------------\n");
			$baseCommand = "php ".__DIR__."/hostHelper.php ".escapeshellarg($domain)." ".dirname(__DIR__, 2)."/CirrusSearch/maintenance/forceSearchIndex.php --queue --maxJobs 3 2>&1";
			$output = shell_exec($baseCommand);
			$this->output($output);
		}
	}

	/**
	 * Run the updater and get output.
	 *
	 * @access	private
	 * @param	string	Domain Name
	 * @param	string	Stuff to tack on to the end of the output.
	 * @return	string	Command Output
	 */
	private function runAndGetOutput($domain, $extra = '') {
		$baseCommand = "php ".__DIR__."/hostHelper.php ".escapeshellarg($domain)." ".dirname(__DIR__, 2)."/CirrusSearch/maintenance/updateSearchIndexConfig.php".(!empty($extra) ? ' '.$extra : '')." 2>&1";
		$this->output($baseCommand."\n");

		return shell_exec($baseCommand);
	}
}

$maintClass = 'fixSearchIndex';
require_once(RUN_MAINTENANCE_IF_MAIN);
