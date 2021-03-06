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
}
