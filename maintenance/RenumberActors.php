<?php
/**
 * Renumber actors in the wiki database
 *
 * @package   HydraCore
 * @author    Robert Nix
 * @copyright (c) 2020 Fandom Inc.
 * @license   GPL-2.0-or-later
 * @link      https://gitlab.com/hydrawiki
 **/

require_once dirname(__DIR__, 3) . '/maintenance/Maintenance.php';

use MediaWiki\MediaWikiServices;

/**
 * Maintenance script to renumber actors in the wiki database to match the
 * actor IDs of the shared actor table.  For use on a wiki which has its own
 * divergent actor table.
 *
 * @ingroup Maintenance
 */
class RenumberActors extends LoggedUpdateMaintenance {
	/**
	 * Cache for local actor to shared actor
	 *
	 * @var array
	 */
	private $localActorIdToSharedActorId = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription('Renumbers actors for a wiki database.');
		$this->addOption('finalize', 'Switch in new tables at the end');
		$this->setBatchSize(1000);
	}

	/**
	 * Update key used for updatelog
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
		$this->renumberTable(
			'revision_actor_temp',
			[
				'revactor_rev',
				'revactor_actor',
				'revactor_timestamp',
				'revactor_page'
			],
			'revactor_actor'
		);
		$this->renumberTable(
			'archive',
			[
				'ar_id',
				'ar_namespace',
				'ar_title',
				'ar_comment_id',
				'ar_user',
				'ar_user_text',
				'ar_actor',
				'ar_timestamp',
				'ar_minor_edit',
				'ar_rev_id',
				'ar_text_id',
				'ar_deleted',
				'ar_len',
				'ar_page_id',
				'ar_parent_id',
				'ar_sha1',
				'ar_content_model',
				'ar_content_format'
			],
			'ar_actor'
		);
		$this->renumberTable(
			'ipblocks',
			[
				'ipb_id',
				'ipb_address',
				'ipb_user',
				'ipb_by',
				'ipb_by_text',
				'ipb_by_actor',
				'ipb_reason_id',
				'ipb_timestamp',
				'ipb_auto',
				'ipb_anon_only',
				'ipb_create_account',
				'ipb_enable_autoblock',
				'ipb_expiry',
				'ipb_range_start',
				'ipb_range_end',
				'ipb_deleted',
				'ipb_block_email',
				'ipb_allow_usertalk',
				'ipb_parent_block_id',
				'ipb_sitewide'
			],
			'ipb_by_actor'
		);
		$this->renumberTable(
			'image',
			[
				'img_name',
				'img_size',
				'img_width',
				'img_height',
				'img_metadata',
				'img_bits',
				'img_media_type',
				'img_major_mime',
				'img_minor_mime',
				'img_description_id',
				'img_user',
				'img_user_text',
				'img_actor',
				'img_timestamp',
				'img_sha1'
			],
			'img_actor'
		);
		$this->renumberTable(
			'oldimage',
			[
				'oi_name',
				'oi_archive_name',
				'oi_size',
				'oi_width',
				'oi_height',
				'oi_bits',
				'oi_description_id',
				'oi_user',
				'oi_user_text',
				'oi_actor',
				'oi_timestamp',
				'oi_metadata',
				'oi_media_type',
				'oi_major_mime',
				'oi_minor_mime',
				'oi_deleted',
				'oi_sha1'
			],
			'oi_actor'
		);
		$this->renumberTable(
			'filearchive',
			[
				'fa_id',
				'fa_name',
				'fa_archive_name',
				'fa_storage_group',
				'fa_storage_key',
				'fa_deleted_user',
				'fa_deleted_timestamp',
				'fa_deleted_reason_id',
				'fa_size',
				'fa_width',
				'fa_height',
				'fa_metadata',
				'fa_bits',
				'fa_media_type',
				'fa_major_mime',
				'fa_minor_mime',
				'fa_description_id',
				'fa_user',
				'fa_user_text',
				'fa_actor',
				'fa_timestamp',
				'fa_deleted',
				'fa_sha1'
			],
			'fa_actor'
		);
		$this->renumberTable(
			'recentchanges',
			[
				'rc_id',
				'rc_timestamp',
				'rc_user',
				'rc_user_text',
				'rc_actor',
				'rc_namespace',
				'rc_title',
				'rc_comment_id',
				'rc_minor',
				'rc_bot',
				'rc_new',
				'rc_cur_id',
				'rc_this_oldid',
				'rc_last_oldid',
				'rc_type',
				'rc_source',
				'rc_patrolled',
				'rc_ip',
				'rc_old_len',
				'rc_new_len',
				'rc_deleted',
				'rc_logid',
				'rc_log_type',
				'rc_log_action',
				'rc_params'
			],
			'rc_actor'
		);
		$this->renumberTable(
			'logging',
			[
				'log_id',
				'log_type',
				'log_action',
				'log_timestamp',
				'log_user',
				'log_user_text',
				'log_actor',
				'log_namespace',
				'log_title',
				'log_page',
				'log_comment_id',
				'log_params',
				'log_deleted'
			],
			'log_actor'
		);

		if ($this->hasOption('finalize')) {
			$this->finalizeTable('revision_actor_temp');
			$this->finalizeTable('archive');
			$this->finalizeTable('ipblocks');
			$this->finalizeTable('image');
			$this->finalizeTable('oldimage');
			$this->finalizeTable('filearchive');
			$this->finalizeTable('recentchanges');
			$this->finalizeTable('logging');
		}

		return true;
	}

	/**
	 * Renumber actor ids in a table.
	 *
	 * @param string   $tableName   The name of the table
	 * @param string[] $columns     The names of the table's columns
	 * @param string   $actorColumn The name of the table's actor id column
	 *
	 * @return void
	 */
	private function renumberTable(string $tableName, array $columns, string $actorColumn) {
		$newTableName = $this->getTempTableName($tableName);
		$this->output("Renumbering $tableName into $newTableName\n");

		$dbr = $this->getDB(DB_REPLICA);
		$dbw = $this->getDB(DB_MASTER);

		$dbw->query(
			'DROP TABLE IF EXISTS ' . $dbw->addIdentifierQuotes($newTableName)
		);
		$dbw->query(
			'CREATE TABLE ' . $dbw->addIdentifierQuotes($newTableName) .
			' LIKE ' . $dbw->addIdentifierQuotes($tableName)
		);

		$lastId = 0;
		$count = 0;
		$newRows = [];
		$res = $dbr->select(
			$tableName,
			$columns + ['actor_user', 'actor_name'],
			[],
			__METHOD__,
			[],
			[
				'actor' => [
					'LEFT JOIN',
					'actor_id=' . $dbr->addIdentifierQuotes($actorColumn)
				]
			]
		);
		while ($row = $dbr->fetchRow($res)) {
			$sharedActorId = $this->getSharedActorId($row[$actorColumn]);
			$newRows[] = [$actorColumn => $sharedActorId] + $row;
			$count++;
		}
		if (count($newRows) == 0) {
			return;
		}
		$dbw->insert($newTableName, $newRows);

		$this->output("Converted $count rows from $tableName\n");
	}

	/**
	 * Switch in the new table
	 *
	 * @param string   $tableName   The name of the table
	 *
	 * @return void
	 */
	private function finalizeTable(string $tableName) {
		$newTableName = $this->getTempTableName($tableName);
		$this->output("Dropping $tableName and renaming $newTableName\n");

		$dbw = $this->getDB(DB_MASTER);

		$dbw->query(
			'DROP TABLE ' . $dbw->addIdentifierQuotes($tableName)
		);
		$dbw->query(
			'ALTER TABLE ' . $dbw->addIdentifierQuotes($newTableName) .
			'RENAME TO ' . $dbw->addIdentifierQuotes($tableName)
		);
	}

	/**
	 * Find the shared actor id corresponding to a local actor id.
	 *
	 * @param int $localActorId
	 *
	 * @return int
	 */
	private function getSharedActorId(int $localActorId): int {
		if (isset($this->localActorIdToSharedActorId[$localActorId])) {
			return $this->localActorIdToSharedActorId[$localActorId];
		}

		$dbr = $this->getDB(DB_REPLICA);
		$localRow = (array)$dbr->selectRow(
			'actor',
			['actor_user', 'actor_name'],
			['actor_id' => $localActorId]
		);

		if ($localRow === false) {
			throw new Exception("Cannot find actor $localActorId in local table");
		}

		$actorDbr = $this->getDBForSharedActor(DB_REPLICA);
		if ($localRow['actor_user'] == null) {
			$sharedRow = (array)$actorDbr->selectRow(
				'actor',
				['actor_id'],
				['actor_name' => $localRow['actor_name']]
			);

			if ($sharedRow === false) {
				throw new Exception("Cannot find actor by name {$localRow['actor_name']} in shared table");
			}
		} else {
			$sharedRow = (array)$actorDbr->selectRow(
				'actor',
				['actor_id', 'actor_name'],
				['actor_user' => $localRow['actor_user']]
			);

			if ($sharedRow === false) {
				throw new Exception("Cannot find actor by userid {$localRow['actor_user']} in shared table");
			}

			if ($sharedRow['actor_name'] !== $localRow['actor_name']) {
				$this->output(
					"... assuming user {$localRow['actor_name']}/{$localRow['actor_user']} " .
					"was renamed to {$sharedRow['actor_name']}\n"
				);
			}
		}

		$this->localActorIdToSharedActorId[$localActorId] = $sharedRow['actor_id'];
		return $sharedRow['actor_id'];
	}

	/**
	 * Return a database connection which uses $wgSharedDB for actor table
	 * queries.
	 *
	 * @param int $dbtype DB_REPLICA or DB_MASTER
	 *
	 * @return \Wikimedia\Rdbms\IDatabase
	 */
	private function getDBForSharedActor(int $dbtype = DB_REPLICA) {
		global $wgSharedTables, $wgSharedDB, $wgSharedSchema, $wgSharedPrefix;

		// Ensure actor uses $wgSharedDB for this connection
		$lbf = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lb = $lbf->getMainLB();
		$lb->setTableAliases(
			array_fill_keys(
				$wgSharedTables + ['actor'],
				[
					'dbname' => $wgSharedDB,
					'schema' => $wgSharedSchema,
					'prefix' => $wgSharedPrefix
				]
			)
		);

		return $lb->getConnection($dbtype);
	}

	/**
	 * Return the name of the temporary mirror for a table.
	 *
	 * @param string $tableName
	 *
	 * @return string Temporary table name
	 */
	private function getTempTableName(string $tableName): string {
		return 'new_' . $tableName;
	}
}

$maintClass = 'RenumberActors';
require_once RUN_MAINTENANCE_IF_MAIN;
