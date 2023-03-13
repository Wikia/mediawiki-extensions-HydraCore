<?php
/**
 * Sync users from the master database to each child cluster
 *
 * @package   HydraCore
 * @author    Robert Nix
 * @copyright (c) 2019 Wikia Inc
 * @license   GPL-2.0-or-later
 * @link      https://gitlab.com/hydrawiki
 **/

require_once dirname(__DIR__, 3) . '/maintenance/Maintenance.php';

use MediaWiki\MediaWikiServices;

/**
 * Maintenance script to populate child wiki database clusters with users from
 * the master database.  This is intended to be ran as part of the MediaWiki
 * 1.33 upgrade, on only the master wiki, before cleanupUsersWithNoId and
 * before update.
 *
 * @ingroup Maintenance
 */
class CopyMasterUsersToClusters extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription('Copies users from the master wiki to each database cluster user table');
		$this->addOption('start', 'User ID to start at', false, true);
		$this->setBatchSize(500);
	}

	public function execute() {
		global $wgChildClusterServers;

		$clusterDBs = [];
		foreach ($wgChildClusterServers as $clusterName) {
			$clusterDBs[$clusterName] = MediaWikiServices::getInstance()
				->getDBLoadBalancerFactory()
				->getExternalLB($clusterName)
				->getConnection(DB_PRIMARY);
		}

		$startFrom = $this->getOption('start', 1);
		$dbw = $this->getDB(DB_PRIMARY);
		$next = 'user.user_id >= ' . intval($startFrom);
		// save renames for the end
		$needRename = [];
		$renameCount = 0;
		while (true) {
			// grab user batch from master
			$res = $dbw->select(
				['user', 'user_global'],
				[
					'user_id' => 'user.user_id',
					'user_name',
					'user_touched',
					'user_registration',
					'user_editcount',
					'global_id'
				],
				$next,
				__METHOD__,
				['LIMIT' => $this->mBatchSize],
				['user' => ['LEFT JOIN', 'user.user_id=user_global.user_id']]
			);
			$userIdToRow = [];
			$maxId = 0;
			$minId = 0;
			while ($row = $res->fetchRow()) {
				$id = $row['user_id'];
				$userIdToRow[$id] = $row;
				if ($id > $maxId) {
					$maxId = $id;
				}
				if (!$minId || $id < $minId) {
					$minId = $id;
				}
			}
			if ($maxId == 0) {
				break;
			}

			$this->output("... $minId .. $maxId\n");
			$next = 'user.user_id > ' . $maxId;
			// for each user, take lowest user_registration
			// find highest user_touched; if name differs from master name,
			// rename
			// for any cluster where no user row returned, insert
			// propagate user_registration and user_name as necessary
			//
			// count for output
			foreach ($wgChildClusterServers as $clusterName) {
				$insertCount = 0;
				$cluster = $clusterDBs[$clusterName];
				$res = $cluster->select(
					['user', 'user_global'],
					[
						'user_id' => 'user.user_id',
						'user_name',
						'user_touched',
						'user_registration',
						'user_editcount',
						'global_id'
					],
					'user.user_id >= ' . $minId . ' AND user.user_id <= ' . $maxId,
					__METHOD__,
					['LIMIT' => $this->mBatchSize],
					['user' => ['LEFT JOIN', 'user.user_id=user_global.user_id']]
				);
				$userIdToClusterRow = [];
				while ($row = $res->fetchRow()) {
					$id = $row['user_id'];
					$userIdToClusterRow[$id] = $row;
				}
				$userInsert = [];
				$userGlobalInsert = [];
				foreach ($userIdToRow as $id => $masterRow) {
					if (!array_key_exists($id, $userIdToClusterRow)) {
						$userInsert[] = [
							'user_id' => $masterRow['user_id'],
							'user_name' => $masterRow['user_name'],
							'user_touched' => $masterRow['user_touched'],
							'user_registration' => $masterRow['user_registration'],
							'user_editcount' => $masterRow['user_editcount'],
						];
						$userGlobalInsert[] = [
							'user_id' => $masterRow['user_id'],
							'global_id' => $masterRow['global_id'],
						];
						$insertCount++;
						continue;
					}
					$clusterRow = $userIdToClusterRow[$id];
					if ($clusterRow['user_touched'] > $masterRow['user_touched']) {
						if ($clusterRow['user_name'] != $masterRow['user_name']) {
							$this->output("... ! $clusterName/$id is newer and has different name than master\n");
							$needRename[$id] = $clusterRow['user_name'];
							$renameCount++;
							$masterRow['user_touched'] = $clusterRow['user_touched'];
						}
					}
				}
				if ($insertCount == 0) {
					continue;
				}
				$this->output("... -> [$clusterName] Inserting $insertCount users ...\n");
				// Insert users
				$cluster->insert(
					'user',
					$userInsert,
					__METHOD__,
					['IGNORE']
				);
				$this->output("... -> [$clusterName] " . $cluster->affectedRows() . " user rows affected\n");
				// Can IGNORE here since links will be updated to this value eventually anyway.
				$cluster->insert(
					'user_global',
					$userGlobalInsert,
					__METHOD__,
					['IGNORE']
				);
				$this->output("... -> [$clusterName] " . $cluster->affectedRows() . " user_global rows affected\n");
			}
		}
		$this->output("Users in need of renames: $renameCount\n");
		foreach ($needRename as $id => $name) {
			$this->output("... $id => $name\n");
		}
	}
}

$maintClass = CopyMasterUsersToClusters::class;
require_once RUN_MAINTENANCE_IF_MAIN;
