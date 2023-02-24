<?php
/**
 * Zero Orphaned Users Maintenance Script
 *
 * @package   HydraCore
 * @copyright (c) 2019 Curse Inc.
 * @license   GPL-2.0-or-later
 * @link      https://gitlab.com/hydrawiki
**/

use Wikimedia\Rdbms\IDatabase;

require_once dirname(dirname(dirname(__DIR__))) . '/maintenance/Maintenance.php';

/**
 * Maintenance script that cleans up tables that have orphaned users.
 */
class ZeroOrphanedUsers extends LoggedUpdateMaintenance {
	private $prefix;

	private $table;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription('Zeros out orphaned users on certain tables.');
		$this->addOption('prefix', 'Interwiki prefix to apply to the usernames', true, true, 'p');
		$this->addOption('table', 'Only clean up this table', false, true);
		$this->setBatchSize(100);
	}

	/**
	 * Return an unique name to logged this maintenance as being done.
	 *
	 * @return string
	 */
	protected function getUpdateKey() {
		return __CLASS__;
	}

	/**
	 * Do database updates for all tables.
	 *
	 * @return boolean True
	 */
	protected function doDBUpdates() {
		$this->prefix = $this->getOption('prefix');
		$this->table = $this->getOption('table', null);

		$this->cleanup(
			'revision',
			'rev_id',
			'rev_user',
			'rev_user_text',
			[
				'rev_user > 0',
				'rev_user < 40275837'
			],
			['rev_timestamp', 'rev_id']
		);
		$this->cleanup(
			'archive',
			'ar_id',
			'ar_user',
			'ar_user_text',
			[
				'ar_user > 0',
				'ar_user < 40275837'
			],
			['ar_id']
		);
		$this->cleanup(
			'logging',
			'log_id',
			'log_user',
			'log_user_text',
			[
				'log_user > 0',
				'log_user < 40275837'
			],
			['log_timestamp', 'log_id']
		);
		$this->cleanup(
			'image',
			'img_name',
			'img_user',
			'img_user_text',
			[
				'img_user > 0',
				'img_user < 40275837'
			],
			['img_timestamp', 'img_name']
		);
		$this->cleanup(
			'oldimage',
			['oi_name', 'oi_timestamp'],
			'oi_user',
			'oi_user_text',
			[
				'oi_user > 0',
				'oi_user < 40275837'
			],
			['oi_name', 'oi_timestamp']
		);
		$this->cleanup(
			'filearchive',
			'fa_id',
			'fa_user',
			'fa_user_text',
			[
				'fa_user > 0',
				'fa_user < 40275837'
			],
			['fa_id']
		);
		$this->cleanup(
			'ipblocks',
			'ipb_id',
			'ipb_by',
			'ipb_by_text',
			[
				'ipb_by > 0',
				'ipb_by < 40275837'
			],
			['ipb_id']
		);
		$this->cleanup(
			'recentchanges',
			'rc_id',
			'rc_user',
			'rc_user_text',
			[
				'rc_user > 0',
				'rc_user < 40275837'
			],
			['rc_id']
		);

		return true;
	}

	/**
	 * Cleanup a table
	 *
	 * @param string          $table      Table to migrate
	 * @param string|string[] $primaryKey Primary key of the table.
	 * @param string          $idField    User ID field name
	 * @param string          $nameField  User name field name
	 * @param array           $conds      Query conditions
	 * @param string[]        $orderby    Fields to order by
	 *
	 * @return void
	 */
	protected function cleanup(
		$table, $primaryKey, $idField, $nameField, array $conds, array $orderby
	) {
		if ($this->table !== null && $this->table !== $table) {
			return;
		}

		$primaryKey = (array)$primaryKey;
		$pkFilter = array_flip($primaryKey);
		$this->output(
			"Beginning cleanup of $table\n"
		);

		$dbw = $this->getDB(DB_PRIMARY);
		$next = '1=1';
		$countAssigned = 0;
		$countNamed = 0;
		$countPrefixed = 0;
		$countZeroed = 0;
		while (true) {
			// Fetch the rows needing update
			$res = $dbw->select(
				$table,
				array_merge($primaryKey, [$idField, $nameField], $orderby),
				array_merge($conds, [$next]),
				__METHOD__,
				[
					'ORDER BY' => $orderby,
					'LIMIT' => $this->mBatchSize,
				]
			);
			if (!$res->numRows()) {
				break;
			}

			// Update the existing rows
			foreach ($res as $row) {
				$name = $row->$nameField;
				$userIdGiven = (int)$row->$idField;
				$userIdTest = User::idFromName($name);
				if ($userIdTest !== null) {
					$userIdTest = intval($userIdTest);
				}
				$usable = User::isUsableName($name);
				$set = [];
				$errors = [];

				if (empty($name)) {
					$user = User::newFromId($userIdGiven);
					$user->load();
					if (!$user->getId()) {
						$name = '@Hippopotamus';
						$set = [
							$nameField => substr($this->prefix . '>' . $name, 0, 255)
						];
						$counter = &$countPrefixed;
					} else {
						$name = $user->getName();
						$userIdTest = $user->getId();
						$set = [
							$idField => $userIdGiven,
							$nameField => substr($name, 0, 255)
						];
						$counter = &$countNamed;
					}
					$errors[] = "Assigning name {$name}";
				}

				if ($userIdTest === null) {
					$errors[] = "Could not find user ID {$userIdGiven}";
					$set += [$idField => 0];
					$counter = &$countZeroed;
				} elseif ($userIdGiven !== $userIdTest) {
					$errors[] = "Found user ID {$userIdTest} to assign: {$name}, was {$userIdGiven}";
					$set += [$idField => $userIdTest];
					$counter = &$countAssigned;
				}

				if (empty($set)) {
					continue;
				}
				$this->output(implode(', ', $errors)."\n");

				$dbw->update(
					$table,
					$set,
					array_intersect_key((array)$row, $pkFilter),
					__METHOD__
				);
				$counter += $dbw->affectedRows();
			}

			list( $next, $display ) = $this->makeNextCond($dbw, $orderby, $row);
			$this->output("... $display\n");
			wfWaitForSlaves();
		}

		$this->output(
			"Cleanup complete: Assigned {$countAssigned}, zeroed {$countZeroed}, fixed {$countNamed} names, and prefixed {$countPrefixed} row(s)\n"
		);
	}

	/**
	 * Calculate a "next" condition and progress display string
	 *
	 * @param IDatabase $dbw
	 * @param string[]  $indexFields Fields in the index being ordered by
	 * @param object    $row         Database row
	 *
	 * @return string[] [ string $next, string $display ]
	 */
	private function makeNextCond($dbw, $indexFields, $row) {
		$next = '';
		$display = [];
		for ($i = count($indexFields) - 1; $i >= 0; $i--) {
			$field = $indexFields[$i];
			$display[] = $field . '=' . $row->$field;
			$value = $dbw->addQuotes($row->$field);
			if ($next === '') {
				$next = "$field > $value";
			} else {
				$next = "$field > $value OR $field = $value AND ($next)";
			}
		}
		$display = implode(' ', array_reverse($display));
		return [$next, $display];
	}
}

$maintClass = ZeroOrphanedUsers::class;
require_once RUN_MAINTENANCE_IF_MAIN;
