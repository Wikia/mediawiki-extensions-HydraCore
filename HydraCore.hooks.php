<?php
/**
 * Curse Inc.
 * HydraCore
 * HydraCore Hooks
 *
 * @author		Telshin
 * @copyright	(c) 2012 Curse Inc.
 * @license		All Rights Reserved
 * @package		HydraCore
 * @link		http://www.curse.com/
 *
**/

if (!defined('MEDIAWIKI')) {
	echo("This is an extension to the MediaWiki software and is not a valid entry point.\n");
	die(-1);
}

class HydraCoreHooks {
	/**
	 * Hooks Initialized
	 *
	 * @var		boolean
	 */
	private static $initialized = false;

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
	 * Setup all the parser functions
	 * @param	Parser	object
	 */
	static public function onParserFirstCallInit(Parser &$parser) {
		$parser->setFunctionHook('numberofcontributors', 'HydraCore::numberofcontributors');

		return true;
	}
}
