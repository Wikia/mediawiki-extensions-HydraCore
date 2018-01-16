<?php
/**
 * Curse Inc.
 * HydraCore
 * Font Upload Special Page
 *
 * @author		Alexia E. Smith
 * @copyright	(c) 2014 Curse Inc.
 * @license		All Rights Reserved
 * @package		HydraCore
 * @link		http://www.curse.com/
 *
**/

class SpecialFontManager extends SpecialPage {
	/**
	 * Output HTML
	 *
	 * @var		string
	 */
	private $content;

	/**
	 * Main Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct() {
		parent::__construct('FontManager');

		$this->wgRequest	= $this->getRequest();
		$this->wgUser		= $this->getUser();
		$this->output		= $this->getOutput();
	}

	/**
	 * Main Executor
	 *
	 * @access	public
	 * @param	string	Sub page passed in the URL.
	 * @return	void	[Outputs to screen]
	 */
	public function execute($subpage) {
		if (!$this->wgUser->isAllowed('font_manager')) {
			throw new PermissionsError('font_manager');
			return;
		}

		$this->templates = new TemplateFontManager;

		$this->output->addModules('ext.hydraCore.fontManager');

		$this->setHeaders();

		$this->fontManagerPage();

		$this->output->addHTML($this->content);
	}

	/**
	 * Font Upload Page
	 *
	 * @access	public
	 * @return	void	[Outputs to screen]
	 */
	public function fontManagerPage() {
		$config = ConfigFactory::getDefaultInstance()->makeConfig('hydracore');
		$ceFontPath = $config->get('CEFontPath');


		if (!is_dir($ceFontPath)) {
			throw new MWException(__METHOD__.": The font path is not set or the directory does not exist.  This must be set and exist before using the font manager.");
		}

		$fontFolder = dir($ceFontPath);

		$upload = null;
		if ($this->wgRequest->getVal('action') == 'upload') {
			if (!$this->wgUser->isAllowed('font_upload')) {
				throw new PermissionsError('font_upload');
				return;
			}
			$upload = $this->fontManagerUpload();
		}

		$fonts = [];
		if ($fontFolder !== false) {
			while (($file = $fontFolder->read()) !== false) {
				if ($file == '.' || $file == '..') {
					continue;
				}
				$_font = \HydraCore\Font::loadFromFile($file, $ceFontPath.DIRECTORY_SEPARATOR.$file);

				if ($_font !== false) {
					$fonts[] = $_font;
				}
			}
		}

		$this->output->setPageTitle(wfMessage('fontmanager'));
		$this->content = $this->templates->fontManagerPage($fonts, $upload);
	}

	/**
	 * Handle Font Uploads
	 *
	 * @access	public
	 * @return	boolean	Successful Upload
	 */
	public function fontManagerUpload() {
		$config = ConfigFactory::getDefaultInstance()->makeConfig('hydracore');
		$ceFontPath = $config->get('CEFontPath');

		$success = false;

		$file = $this->wgRequest->getUpload('font_file');

		if ($file instanceof WebRequestUpload) {
			$_font = \HydraCore\Font::loadFromFile($file->getName(), $file->getTempName());

			if ($_font !== false) {
				$success = $_font->moveFile($ceFontPath, $file->getName(), $this->wgRequest->getBool('overwrite'));
			}
		}

		return $success;
	}

	/**
	 * Hides special page from SpecialPages special page.
	 *
	 * @access	public
	 * @return	boolean
	 */
	public function isListed() {
		if ($this->wgUser->isAllowed('font_manager')) {
			return true;
		}
		return false;
	}

	/**
	 * Lets others determine that this special page is restricted.
	 *
	 * @access	public
	 * @return	boolean	True
	 */
	public function isRestricted() {
		return true;
	}
}
