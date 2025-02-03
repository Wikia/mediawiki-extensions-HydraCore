<?php

use MediaWiki\Api\ApiBase;
use MediaWiki\Api\Hook\APIGetDescriptionMessagesHook;

/**
 * Curse Inc.
 * HydraCore
 * HydraCore Hooks
 *
 * @author        Telshin
 * @copyright    (c) 2012 Curse Inc.
 * @license        GNU General Public License v2.0 or later
 * @package        HydraCore
 * @link        https://gitlab.com/hydrawiki
 *
 */
class HydraCoreHooks implements APIGetDescriptionMessagesHook {

	/**
	 * APIGetDescriptionMessages hook handler
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/APIGetDescriptionMessages
	 *
	 * @param ApiBase $module
	 * @param array|string &$msg
	 */
	public function onAPIGetDescriptionMessages( $module, &$msg ): void {
		if ( $module->getModuleName() == 'parse' ) {
			$msg[] = $module->msg( 'api-parse-modified-hydracore' );
		}
	}
}
