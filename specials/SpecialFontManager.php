<?php

use HydraCore\Font;
use MediaWiki\MediaWikiServices;

/**
 * Curse Inc.
 * HydraCore
 * Font Upload Special Page
 *
 * @author        Alexia E. Smith
 * @copyright    (c) 2014 Curse Inc.
 * @license        GNU General Public License v2.0 or later
 * @package        HydraCore
 * @link        https://gitlab.com/hydrawiki
 *
 */
class SpecialFontManager extends SpecialPage {
	/**
	 * Output HTML
	 * @var string
	 */
	private $content;
	/**
	 * @var TemplateFontManager
	 */
	private $templates;
	/**
	 * @var WebRequest
	 */
	private $wgRequest;
	/**
	 * @var User
	 */
	private $wgUser;
	/**
	 * @var OutputPage
	 */
	private $output;

	/**
	 * Main Constructor
	 * @return    void
	 */
	public function __construct() {
		parent::__construct('FontManager');

		$this->wgRequest	= $this->getRequest();
		$this->wgUser		= $this->getUser();
		$this->output		= $this->getOutput();
	}

	/**
	 * Main Executor
	 * @param string    Sub page passed in the URL.
	 * @return void [Outputs to screen]
	 */
	public function execute( $subpage ) {
		if ( !$this->wgUser->isAllowed( 'font_manager' ) ) {
			throw new PermissionsError( 'font_manager' );
		}

		$this->templates = new TemplateFontManager();
		$this->output->addModuleStyles( [ 'ext.hydraCore.fontManager.styles' ] );
		$this->setHeaders();
		$this->fontManagerPage();
		$this->output->addHTML( $this->content );
	}

	/**
	 * Font Upload Page
	 */
	public function fontManagerPage(): void {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'hydracore' );
		$ceFontPath = $config->get( 'CEFontPath' );

		if ( !is_dir( $ceFontPath ) ) {
			throw new MWException(
				"The font path is not set or the directory does not exist." .
				" This must be set and exist before using the font manager."
			);
		}

		$fontFolder = dir( $ceFontPath );

		$upload = null;
		if ( $this->wgRequest->getVal( 'action' ) == 'upload' ) {
			if ( !$this->wgUser->isAllowed( 'font_upload' ) ) {
				throw new PermissionsError( 'font_upload' );
			}
			$upload = $this->fontManagerUpload();
		}

		$fonts = [];
		if ( $fontFolder !== false ) {
			while ( ( $file = $fontFolder->read() ) !== false ) {
				if ( $file == '.' || $file == '..' ) {
					continue;
				}
				$_font = Font::loadFromFile( $file, $ceFontPath . DIRECTORY_SEPARATOR . $file );

				if ( $_font !== false ) {
					$fonts[$_font->getFileName()] = $_font;
				}
			}
		}
		ksort( $fonts );

		$this->output->setPageTitle( wfMessage( 'fontmanager' ) );
		$this->content = $this->templates->fontManagerPage( $fonts, $upload );
	}

	/**
	 * Handle Font Uploads
	 * @return bool Successful Upload
	 */
	public function fontManagerUpload() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'hydracore' );
		$ceFontPath = $config->get( 'CEFontPath' );

		$success = false;

		$file = $this->wgRequest->getUpload( 'font_file' );

		if ( $file instanceof WebRequestUpload ) {
			$_font = Font::loadFromFile( $file->getName(), $file->getTempName() );

			if ( $_font !== false ) {
				$success = $_font->moveFile( $ceFontPath, $file->getName(), $this->wgRequest->getBool( 'overwrite' ) );
			}
		}

		return $success;
	}

	/**
	 * Hides special page from SpecialPages special page.
	 * @return bool
	 */
	public function isListed(): bool {
		if ( $this->wgUser->isAllowed( 'font_manager' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Lets others determine that this special page is restricted.
	 */
	public function isRestricted(): bool {
		return true;
	}
}
