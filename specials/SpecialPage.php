<?php
/**
 * Curse Inc.
 * Dynamic Settings
 * Generic Special Page
 *
 * @author    Noah Manneschmidt
 * @copyright (c) 2015 Curse Inc.
 * @license   All Rights Reserved
 * @package   Dynamic Settings
 * @link      http://www.curse.com/
**/
namespace HydraCore;

class SpecialPage extends \SpecialPage {
	/**
	 * @param string $name Name of the special page
	 * @param string $restriction Required user right to use the special page
	 * @param bool $listed When true, page will be listed when current user is allowed
	 */
	public function __construct($name = '', $restriction = '', $listed = true) {
		parent::__construct($name, $restriction, $listed);

		global $wgMemc;
		$this->wgMemc    = $wgMemc;
		$this->wgRequest = $this->getRequest();
		$this->wgUser    = $this->getUser();
		$this->output    = $this->getOutput();

		$this->DB = wfGetDB(DB_MASTER);
	}

	/**
	 * Return the group name for this special page.
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getGroupName() {
		return 'other';
	}

	// only list when we want it listed, and when user is allowed to use
	public function isListed() {
		return parent::isListed() && $this->userCanExecute($this->wgUser);
	}
}
