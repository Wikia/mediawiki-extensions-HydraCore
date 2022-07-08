<?php
/**
 * Curse Inc.
 * Dynamic Settings
 * Generic Special Page
 *
 * @author    Noah Manneschmidt
 * @copyright	(c) 2015 Curse Inc.
 * @license		GNU General Public License v2.0 or later
 * @package   Dynamic Settings
 * @link      https://gitlab.com/hydrawiki
 */
namespace HydraCore;

class SpecialPage extends \SpecialPage {
	/** @var \WebRequest */
	protected $wgRequest;
	/** @var \User */
	protected $wgUser;
	/** @var \OutputPage */
	protected $output;

	/**
	 * @param string $name Name of the special page
	 * @param string $restriction Required user right to use the special page
	 * @param bool $listed When true, page will be listed when current user is allowed
	 */
	public function __construct( $name = '', $restriction = '', $listed = true ) {
		parent::__construct( $name, $restriction, $listed );
		$this->wgRequest = $this->getRequest();
		$this->wgUser    = $this->getUser();
		$this->output    = $this->getOutput();
	}

	/**
	 * Return the group name for this special page.
	 *
	 * @protected
	 * @return string
	 */
	protected function getGroupName() {
		return 'other';
	}

	// only list when we want it listed, and when user is allowed to use
	public function isListed() {
		return parent::isListed() && $this->userCanExecute( $this->getUser() );
	}
}
