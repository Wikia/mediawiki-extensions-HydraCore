<?php
/**
 * Curse Inc.
 * HydraCore
 * API superclass that simplifies the process of creating multi-function api endpoints
 *
 * @author        Noah Manneschmidt
 * @copyright    (c) 2014 Curse Inc.
 * @license        GNU General Public License v2.0 or later
 * @package        Curse
 * @link        https://gitlab.com/hydrawiki
 *
 */

use MediaWiki\MediaWikiServices;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Extend this instead of ApiBase, implement getActions, as well as a doAction method for each action
 * Also implement the usual Api methods getDescription and getParamDescription
 */
abstract class HydraApiBase extends ApiBase {
	public function getParamDescription() {
		return [
			'do' => 'The action that should be performed',
			'token' => 'The edit token for the current user, for write actions',
		];
	}

	public function getAllowedParams() {
		$do = $this->getMain()->getVal( 'do' );
		$action = $this->getMain()->getVal( 'action' );

		if ( $action == 'help' && empty( $do ) ) {
			return $this->getDoLinks();
		}

		$childParams = $this->getActions()[$do]['params'];

		// TODO: might not be necessary?
		if ( $this->needsToken() ) {
			$childParams['token'] = [
				'token' => [
					ParamValidator::PARAM_TYPE => 'string',
					ParamValidator::PARAM_REQUIRED => true,
				],
			];
		}

		$childParams['do'] = [
			ParamValidator::PARAM_TYPE => 'string',
			ParamValidator::PARAM_REQUIRED => true,
		];

		return $childParams;
	}

	/**
	 * Add links for valid do params to help page.
	 */
	public function getDoLinks(): array {
		$module = $this->getMain()->getVal( 'modules' );
		$dos = array_keys( $this->getActions() );
		$links = [];
		$urlUtils = MediaWikiServices::getInstance()->getUrlUtils();

		array_map( static function ( $do ) use ( &$links, $module, $urlUtils ) {
			$link = $urlUtils->expand( '/api.php?action=help&modules=' . $module . '&do=' . $do );
			$links[] = '[' . $link . ' ' . $do . ']';
		}, $dos );

		return [
			'do' => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
				ApiBase::PARAM_HELP_MSG => $this->msg( 'hydra-apihelp-do-links', implode( ', ', $links ) ),
			],
		];
	}

	/**
	 * Returns an array that defines the actions that can be taken within this class.
	 * Example:
	 * [
	 *   'search' => [
	 *     'tokenRequired' => false,
	 *     'postRequired' => false,
	 *     'params' => array(), #as if returned by getAllowedParams()
	 *   ],
	 *   'send' => [
	 *     'tokenRequired' => true,
	 *     'postRequired' => true,
	 *     'permissionRequired' => 'api-search',
	 *     'params' => array(), #as if returned by getAllowedParams()
	 *   ]
	 * ]
	 *
	 * @return array
	 */
	abstract public function getActions(): array;

	public function needsToken() {
		$do = $this->getMain()->getVal( 'do' );
		if ( $do ) {
			return $this->getActionParam( 'tokenRequired' ) ? 'csrf' : false;
		}
		return false;
	}

	private function getActionParam( $param ) {
		$action = $this->getMain()->getVal( 'do' );
		$params = $this->getActions()[$action] ?? [];
		return $params[$param] ?? null;
	}

	public function mustBePosted() {
		$do = $this->getMain()->getVal( 'do' );
		if ( $do ) {
			return $this->getActionParam( 'postRequired' ) ?: false;
		}
		return false;
	}

	public function getTokenSalt() {
		return ( $this->needsToken() ? '' : false );
	}

	public function execute() {
		$do = $this->getMain()->getVal( 'do' );
		$method = 'do' . ucfirst( $do );

		if ( !in_array( $do, array_keys( $this->getActions() ) ) ) {
			$this->dieWithError( 'Undefined DO action: ' . $do, 'bad_api_request' );
		}

		if ( !method_exists( $this, $method ) ) {
			$this->dieWithError( 'Undefined method: ' . get_class( $this ) . '::' . $method, 'bad_api_class' );
		}

		$perm = $this->getPermissionRequired();
		if ( $perm && !$this->getUser()->isAllowed( $perm ) ) {
			$this->dieWithError( wfMessage( 'badaccess-groups', $perm, 1 )->text(), 'permission_needed' );
		}

		return $this->$method();
	}

	private function getPermissionRequired() {
		$actions = $this->getActions();
		$perm =
			isset( $actions[$this->getMain()->getVal( 'do' )]['permissionRequired'] ) ? $actions[$this->getMain()
				->getVal( 'do' )]['permissionRequired'] : '';

		return ( strlen( $perm ) ? $perm : false );
	}

	/**
	 * Get a value from a parameter in the request and cast to an integer.
	 *
	 * @param string $key Parameter Name
	 * @param mixed $default [Optional] Default value to return if not found.
	 *
	 * @return int
	 */
	protected function getInt( string $key, $default = 0 ): int {
		return intval( $this->getMain()->getVal( $key, $default ) );
	}
}
