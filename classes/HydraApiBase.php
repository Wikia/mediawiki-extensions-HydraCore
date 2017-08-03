<?php
/**
 * Curse Inc.
 * HydraCore
 * API superclass that simplifies the process of creating multi-function api endpoints
 *
 * @author		Noah Manneschmidt
 * @copyright	(c) 2014 Curse Inc.
 * @license		All Rights Reserved
 * @package		Curse
 * @link		http://www.curse.com/
 *
**/

/**
 * Extend this instead of ApiBase, implement getActions, as well as a doAction method for each action
 * Also implement the usual Api methods getDescription and getParamDescription
 */
abstract class HydraApiBase extends ApiBase {
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
	 * @return	array
	 */
	abstract function getActions();

	public function getParamDescription() {
		return [
			'do' => 'The action that should be performed',
			'token' => 'The edit token for the current user, for write actions',
		];
	}

	public function getAllowedParams() {
		$do = $this->getMain()->getVal('do');

		$childParams = $this->getActions()[$do]['params'];

		// TODO: might not be necessary?
		if ($this->needsToken()) {
			$childParams['token'] = [
				'token' => [
					ApiBase::PARAM_TYPE => 'string',
					ApiBase::PARAM_REQUIRED => true,
				]
			];
		}

		$childParams['do'] = [
			ApiBase::PARAM_TYPE => 'string',
			ApiBase::PARAM_REQUIRED => true,
		];

		return $childParams;
	}

	public function mustBePosted() {
		return $this->getActions()[$this->getMain()->getVal('do')]['postRequired'];
	}

	public function needsToken() {
		return $this->getActions()[$this->getMain()->getVal('do')]['tokenRequired'] ? "csrf" : false;
	}

	public function getTokenSalt() {
		return ($this->needsToken() ? '' : false);
	}

	private function getPermissionRequired() {
		$actions = $this->getActions();
		$perm = isset($actions[$this->getMain()->getVal('do')]['permissionRequired']) ? $actions[$this->getMain()->getVal('do')]['permissionRequired'] : '';

		return (strlen($perm) ? $perm : false);
	}

	public function execute() {
		$do = $this->getMain()->getVal('do');
		$method = 'do'.ucfirst($do);

		if ( !in_array($do, array_keys($this->getActions())) ) {
			$this->dieUsage('Undefined DO action: '.$do, 'bad_api_request');
		}

		if (!method_exists($this, $method)) {
			$this->dieUsage('Undefined method: '.get_class($this).'::'.$method, 'bad_api_class');
		}

		$perm = $this->getPermissionRequired();
		if ($perm && !$this->getUser()->isAllowed($perm)) {
			$this->dieUsage(wfMessage('badaccess-groups', $perm, 1)->text(), 'permission_needed');
		}

		return $this->$method();
	}
}
