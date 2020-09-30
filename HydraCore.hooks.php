<?php
/**
 * Curse Inc.
 * HydraCore
 * HydraCore Hooks
 *
 * @author		Telshin
 * @copyright	(c) 2012 Curse Inc.
 * @license		GNU General Public License v2.0 or later
 * @package		HydraCore
 * @link		https://gitlab.com/hydrawiki
 *
**/

use DynamicSettings\Environment;
use MediaWiki\MediaWikiServices;

class HydraCoreHooks {
	/**
	 * Hooks Initialized
	 *
	 * @var		boolean
	 */
	private static $initialized = false;

	/**
	 * Global Groups Cache
	 * Local User ID => [groups]
	 *
	 * @var		array
	 */
	private static $globalGroups = [];

	/**
	 * Initiates some needed classes.
	 *
	 * @access	public
	 * @return	void
	 */
	static public function init() {
		if (!self::$initialized) {
			define('CE_EXT_DIR', dirname(__FILE__));

			self::$initialized = true;
		}
	}

	/**
	 * Reorganize email preferences (assuming that the Echo extension exists)
	 *
	 * @access	public
	 * @param	object	user whose preferences are being modified
	 * @param	array	Preferences description object, to be fed to an HTMLForm
	 * @return	boolean	true
	 */
	static public function onGetPreferences($user, &$preferences) {
		// only reorganize if the Echo extension exists
		if (isset($preferences['echo-subscriptions'])) {
			// Move these from the main "User profile" tab to the notifications tab
			$emailFields = ['emailaddress', 'emailauthentication', 'disablemail', 'ccmeonemails', 'enotifwatchlistpages', 'enotifminoredits'];
			foreach ($emailFields as $field) {
				if (isset($preferences[$field])) {
					$preferences[$field]['section'] = 'echo/emailsettings';
				}
			}
			// move redundant email reminder to the default tab
			$preferences['echo-emailaddress']['section'] = 'personal/info';
			$preferences['echo-emailaddress']['label-message'] = 'youremail';
		}
		return true;
	}

	/**
	 * Modify the response to say that all IP address can not use HTTPS.  This is a hack work around to allow HTTPS logins, but still have HTTP only so that advertisements can be displayed.
	 *
	 * @access	public
	 * @param	string	The IP address of the device accessing the site.
	 * @param	boolean	Can use HTTPS
	 * @return	boolean	true
	 */
	static public function onCanIPUseHTTPS($ip, &$canDo) {
		$canDo = false;
		return true;
	}

	/**
	 * Force X-Mobile header.
	 *
	 * @access	public
	 * @param	object	Output
	 * @param	object	Skin
	 * @return	void
	 */
	static public function onBeforePageDisplayMobile($output, $skin) {
		$response = $output->getRequest()->response();
		$response->header("X-Mobile: true");
	}

	/**
	 * Hook to fix open redirects when $wgArticlePath is /$1
	 * 
	 * @param Title &$title
	 * @param string &$url
	 */
	static public function onGetLocalURLArticle(Title &$title, string &$url) {
		global $wgServer;

		if (strpos( $url, '//' ) === 0) {
			$url = $wgServer . $url;
		}
	}

	/**
	 * Setup all the parser functions
	 * @param	Parser	object
	 */
	static public function onParserFirstCallInit(Parser &$parser) {
		$parser->setFunctionHook('numberofcontributors', 'HydraCore::numberofcontributors');

		return true;
	}

	/**
	 * Stops the special master only user groups from being added to accounts on child wikis.
	 *
	 * @access	public
	 * @param	object	Mediawiki User Object
	 * @param	string	Group name to be added.
	 * @return	boolean Whether or not to add the group.
	 */
	static public function onUserAddGroup($user, &$group) {
		if (Environment::isMasterWiki()) {
			return true;
		}

		$config = ConfigFactory::getDefaultInstance()->makeConfig('hydracore');
		$masterOnlyUserGroups = (array) $config->get('MasterOnlyUserGroups');
		if (in_array($group, $masterOnlyUserGroups)) {
			return false;
		}

		return true;
	}

	/**
	 * Handles copying local rights into the global level.
	 *
	 * @access	public
	 * @param	object	Mediawiki User Object
	 * @param	array	Existing user groups.
	 * @return	boolean True
	 */
	static public function onUserEffectiveGroups(&$user, &$groups) {
		$userId = $user->getId();
		if (!$userId) {
			return true;
		}

		if (isset(self::$globalGroups[$userId])) {
			$groups = array_merge($groups, self::$globalGroups[$userId]);
		} else {
			$redis = RedisCache::getClient('cache');

			if ($redis !== false) {
				$globalKey = 'groups:global:userId:'.$userId;

				try {
					if (!$redis->exists($globalKey) && Environment::isMasterWiki() && count($user->getGroups())) {
						$redis->set($globalKey, serialize($user->getGroups()));
						$redis->expire($globalKey, 3600);
					} elseif (!Environment::isMasterWiki()) {
						$userGlobalGroups = unserialize($redis->get($globalKey));

						if (is_array($userGlobalGroups)) {
							$groups = array_merge($groups, $userGlobalGroups);
						}
					}
				} catch (RedisException $e) {
					wfDebug(__METHOD__.": Caught RedisException - ".$e->getMessage());
				}
			}

			$groups = array_merge($groups, self::getUserGlobalGroupsFromHelios($userId));

			//Handle turning global groups into the local groups on child wikis.
			if (!Environment::isMasterWiki()) {
				$config = ConfigFactory::getDefaultInstance()->makeConfig('hydracore');
				$configGlobalGroups = (array) $config->get('GlobalGroups');

				foreach ($groups as $group) {
					//$configGlobalGroups contains "global group" => "local group" associations.  A value of false indicates the group is global, but does not have an associated local group.
					if (array_key_exists($group, $configGlobalGroups) && $configGlobalGroups[$group] !== false) {
						$groups[] = $configGlobalGroups[$group];
					}
				}
			}
		}

		if (is_array($groups)) {
			$groups = array_unique($groups);
		}

		self::$globalGroups[$userId] = $groups;

		return true;
	}

	/**
	 * Get groups for a user from the central wiki.
	 */
	private static function getUserGlobalGroupsFromHelios(int $userId): array {
		//Grab global groups from Helios and assemble here.
		$services = MediaWikiServices::getInstance();
		$cache = $services->getMainWANObjectCache();
		$heliosGroups = $cache->getWithSetCallback(
			$cache->makeGlobalKey('global-user-groups', $userId),
			$cache::TTL_HOUR,
			function () use ($services, $userId) {
				$mainConfig = $services->getMainConfig();
				if (!$mainConfig->has('HeliosCentralDatabase')) {
					//Stop breaking staging.
					return [];
				}
				$centralDBname = $mainConfig->get('HeliosCentralDatabase');
				if (empty($centralDBname)) {
					return [];
				}
				$dbr = $services->getDBLoadBalancerFactory()
					->getMainLB( $centralDBname )
					->getConnectionRef(DB_REPLICA, [], $centralDBname);

					$groups = $dbr->selectFieldValues(
						'user_groups',
						'ug_group',
						[ 'ug_user' => $userId ]
					);

					return $groups;
			}
		);

		if (empty($heliosGroups)) {
			$heliosGroups = [];
		}

		return $heliosGroups;
	}

	/**
	 * Handles updating Redis cache with new user groups.
	 *
	 * @access	public
	 * @param	object	User modified.
	 * @param	array	Groups added to user.
	 * @param	array	Groups removed from user.
	 * @param	object	User performing the action.
	 * @return	boolean	true
	 */
	static public function onUserGroupsChanged($user, $groupsAdded, $groupsRemoved, $performer) {
		if (!$user->getId()) {
			return true;
		}

		if (!Environment::isMasterWiki()) {
			//Only the master wiki is intended to populate global groups.
			return true;
		}

		//Don't allow non-bureaucrats to add bureaucrat to an user.
		if (in_array('bureaucrat', $groupsAdded)) {
			if (!in_array('bureaucrat', $performer->getGroups())) {
				$user->removeGroup('bureaucrat');
			}
		}

		$redis = RedisCache::getClient('cache');

		if ($redis !== false) {
			$config = ConfigFactory::getDefaultInstance()->makeConfig('hydracore');
			$configGlobalGroups = (array) $config->get('GlobalGroups');

			$key = 'groups:global:userId:'.$user->getId();
			try {
				if (count($user->getGroups())) {
					//Get the keys from the configured global groups and use them to limit the groups pushed into the global scope.
					$configGlobalGroups = array_keys($configGlobalGroups);
					$globalGroups = array_intersect($configGlobalGroups, $user->getGroups());
					$redis->set($key, serialize($globalGroups));
					$redis->expire($key, 3600);
				} else {
					$redis->del($key);
				}
			} catch (RedisException $e) {
				wfDebug(__METHOD__.": Caught RedisException - ".$e->getMessage());
			}
		}

		return true;
	}

	/**
	 * Add hooks late so that they are ensured to come last.
	 *
	 * @access	public
	 * @return	void
	 */
	static public function addLateHooks() {
		global $wgHooks;
		$wgHooks['APIAfterExecute'][] = 'HydraCoreHooks::onAPIAfterExecute';
	}

	/**
	 * APIGetAllowedParams hook handler
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/APIGetAllowedParams
	 * @param ApiBase $module
	 * @param array|bool $params
	 * @return bool
	 */
	public static function onAPIGetAllowedParams(ApiBase &$module, &$params) {
		if ($module->getModuleName() == 'parse') {
			$params['withads'] = false;
		}
		return true;
	}

	/**
	 * APIGetParamDescriptionMessages hook handler
	 *
	 * @see: https://www.mediawiki.org/wiki/Manual:Hooks/APIGetParamDescriptionMessages
	 * @param ApiBase $module
	 * @param Array|bool $msgs
	 * @return bool
	 */
	public static function onAPIGetParamDescriptionMessages(ApiBase $module, &$msgs) {
		if ($module->getModuleName() == 'parse') {
			$msgs['withads'] = [$module->msg('api-parse-withads-desc')];
		}
		return true;
	}

	/**
	 * APIGetDescriptionMessages hook handler
	 *
	 * @see: https://www.mediawiki.org/wiki/Manual:Hooks/APIGetDescriptionMessages
	 * @param ApiBase $module
	 * @param Array|string $msgs
	 * @return bool
	 */
	public static function onAPIGetDescriptionMessages(ApiBase $module, &$msgs) {
		if ($module->getModuleName() == 'parse') {
			$msgs[] = $module->msg('api-parse-modified-hydracore');
		}
		return true;
	}

	/**
	 * APIAfterExecute hook handler
	 * @see: https://www.mediawiki.org/wiki/Manual:Hooks/
	 * @param ApiBase $module
	 * @return bool
	 */
	public static function onAPIAfterExecute(ApiBase &$module) {
		if ($module->getModuleName() == 'parse') {
			if (defined('ApiResult::META_CONTENT')) {
				$data = $module->getResult()->getResultData();
			} else {
				$data = $module->getResultData();
			}
			$params = $module->extractRequestParams();
			if (isset($data['parse']['text']) && $params['withads']) {
				$result = $module->getResult();
				$result->reset();

				$text = $data['parse']['text'];
				if (is_array($text)) {
					if (defined('ApiResult::META_CONTENT') &&
						isset($text[ApiResult::META_CONTENT])
					) {
						$contentKey = $text[ApiResult::META_CONTENT];
					} else {
						$contentKey = '*';
					}
					$text = $text[$contentKey];
				} else {
					$text = $text;
				}

				$data['parse']['text'] = '<div id="mobileatfmrec">'.HydraHooks::getAdBySlot('mobileatfmrec').'</div>'.$text.'<div id="mobilebtfmrec">'.HydraHooks::getAdBySlot('mobilebtfmrec').'</div>';

				$result->addValue(null, $module->getModuleName(), $data['parse']);
			}
		}
		return true;
	}

	/**
	 * MediaWikiServices hook handler
	 *
	 * @param MediaWikiServices $services The new MediaWikiServices instance
	 *
	 * @return bool
	 */
	public static function onMediaWikiServices($services) {
		global $wgSharedDB, $wgSharedTables, $wgSharedSchema, $wgSharedPrefix;

		// Don't do anything if SharedDB isn't configured.
		if (!($wgSharedDB && $wgSharedTables)) {
			return true;
		}

		// Apply $wgSharedDB table aliases to all LBs created by LBFactory
		$services->addServiceManipulator(
			'DBLoadBalancerFactory',
			function ($lbf, $services) use ($wgSharedDB, $wgSharedTables, $wgSharedSchema, $wgSharedPrefix) {
				$lbf->setTableAliases(
					array_fill_keys(
						$wgSharedTables,
						[
							'dbname' => $wgSharedDB,
							'schema' => $wgSharedSchema,
							'prefix' => $wgSharedPrefix
						]
					)
				);
			}
		);

		return true;
	}
}
