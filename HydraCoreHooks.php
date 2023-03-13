<?php

use MediaWiki\Api\Hook\APIGetDescriptionMessagesHook;
use MediaWiki\Hook\ParserFirstCallInitHook;

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
class HydraCoreHooks implements
	APIGetDescriptionMessagesHook,
	ParserFirstCallInitHook
{

	public function __construct( private HydraCore $core ) {
	}

	/**
	 * Force X-Mobile header.
	 */
	public function onBeforePageDisplayMobile( $output, $skin ): void {
		$response = $output->getRequest()->response();
		$response->header( "X-Mobile: true" );
	}

	/**
	 * Setup all the parser functions
	 */
	public function onParserFirstCallInit( $parser ): void {
		$parser->setFunctionHook( 'numberofcontributors', [$this->core, 'numberOfContributors'] );
	}

	/**
	 * APIGetDescriptionMessages hook handler
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/APIGetDescriptionMessages
	 * @param ApiBase $module
	 * @param array|string &$msgs
	 */
	public function onAPIGetDescriptionMessages( $module, &$msgs ): void {
		if ( $module->getModuleName() == 'parse' ) {
			$msgs[] = $module->msg( 'api-parse-modified-hydracore' );
		}
	}
}
