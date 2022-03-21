<?php

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
class HydraCoreHooks {

	/**
	 * Force X-Mobile header.
	 */
	public static function onBeforePageDisplayMobile( $output, $skin ): void {
		$response = $output->getRequest()->response();
		$response->header( "X-Mobile: true" );
	}

	/**
	 * Setup all the parser functions
	 */
	public static function onParserFirstCallInit( Parser &$parser ): void {
		$parser->setFunctionHook( 'numberofcontributors', 'HydraCore::numberofcontributors' );
	}

	/**
	 * Add hooks late so that they are ensured to come last.
	 */
	public static function addLateHooks(): void {
		global $wgHooks;
		$wgHooks['APIAfterExecute'][] = 'HydraCoreHooks::onAPIAfterExecute';
	}

	/**
	 * APIGetAllowedParams hook handler
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/APIGetAllowedParams
	 * @param ApiBase &$module
	 * @param array|bool &$params
	 */
	public static function onAPIGetAllowedParams( ApiBase &$module, &$params ): void {
		if ( $module->getModuleName() == 'parse' ) {
			$params['withads'] = false;
		}
	}

	/**
	 * APIGetParamDescriptionMessages hook handler
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/APIGetParamDescriptionMessages
	 * @param ApiBase $module
	 * @param array|bool &$msgs
	 */
	public static function onAPIGetParamDescriptionMessages( ApiBase $module, &$msgs ): void {
		if ( $module->getModuleName() == 'parse' ) {
			$msgs['withads'] = [ $module->msg( 'api-parse-withads-desc' ) ];
		}
	}

	/**
	 * APIGetDescriptionMessages hook handler
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/APIGetDescriptionMessages
	 * @param ApiBase $module
	 * @param array|string &$msgs
	 */
	public static function onAPIGetDescriptionMessages( ApiBase $module, &$msgs ): void {
		if ( $module->getModuleName() == 'parse' ) {
			$msgs[] = $module->msg( 'api-parse-modified-hydracore' );
		}
	}

	/**
	 * APIAfterExecute hook handler
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/
	 * @param ApiBase &$module
	 * @throws ApiUsageException
	 */
	public static function onAPIAfterExecute( ApiBase &$module ): void {
		if ( $module->getModuleName() == 'parse' ) {
			$data = $module->getResult()->getResultData();
			$params = $module->extractRequestParams();
			if ( isset( $data['parse']['text'] ) && $params['withads'] ) {
				$result = $module->getResult();
				$result->reset();

				$text = $data['parse']['text'];
				if ( is_array( $text ) ) {
					if ( defined( 'ApiResult::META_CONTENT' ) &&
						 isset( $text[ApiResult::META_CONTENT] )
					) {
						$contentKey = $text[ApiResult::META_CONTENT];
					} else {
						$contentKey = '*';
					}
					$text = $text[$contentKey];
				}

				$data['parse']['text'] =
					'<div id="mobileatfmrec">' . HydraHooks::getAdBySlot( 'mobileatfmrec' ) . '</div>' . $text .
					'<div id="mobilebtfmrec">' . HydraHooks::getAdBySlot( 'mobilebtfmrec' ) . '</div>';

				$result->addValue( null, $module->getModuleName(), $data['parse'] );
			}
		}
	}
}
