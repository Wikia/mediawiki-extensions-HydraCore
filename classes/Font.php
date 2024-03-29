<?php
/**
 * Curse Inc.
 * HydraCore
 * Font Class
 *
 * @author        Alexia E. Smith
 * @copyright    (c) 2014 Curse Inc.
 * @license        GNU General Public License v2.0 or later
 * @package        HydraCore
 * @link        https://gitlab.com/hydrawiki
 *
 */

namespace HydraCore;

use ConfigFactory;

//todo seems that class not in use outside the extension. One usage in \SpecialFontManager
class Font {

	private $data = [];

	public function __construct( private ConfigFactory $configFactory ) {
	}


	/**
	 * Set the font file name.
	 * @param string    Font File Name
	 * @return void
	 */
	public function setFileName( $fileName ) {
		$this->data['file_name'] = $fileName;
	}

	/**
	 * Return the File Type
	 * @return mixed String File Type Extension or False for no File Type.
	 */
	public function getFileType() {
		$ceFontTypes = $this->configFactory->makeConfig( 'hydracore' )->get( 'CEFontTypes' );

		$lastDot = strrpos( $this->data['file_name'], '.' );
		if ( $lastDot !== false ) {
			$type = strtolower( substr( $this->data['file_name'], $lastDot + 1 ) );
			if ( in_array( $type, $ceFontTypes ) ) {
				return $type;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Set the font name.
	 * @param string    Font Name
	 * @return void
	 */
	public function setName( $name ) {
		$this->data['name'] = $name;
	}

	/**
	 * Return the font file name.
	 * @return string Font File Name
	 */
	public function getFileName() {
		return $this->data['file_name'];
	}

	/**
	 * Set the font file path.
	 * @param string    Font File Path
	 * @return void
	 */
	public function setFilePath( $filePath ) {
		$this->data['file_path'] = $filePath;
	}

	/**
	 * Move the file from the current path to a new path.
	 * @param string    File Folder
	 * @param string    File Name
	 * @param boolean    [Optional] Allow file overwriting.
	 * @return bool Success
	 */
	public function moveFile( $fileFolder, $fileName, $overwrite = false ) {
		if ( !is_dir( $fileFolder ) || !is_writable( $fileFolder ) ) {
			return false;
		}

		$filePath = rtrim( $fileFolder, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $fileName;
		if ( $overwrite !== true && file_exists( $filePath ) ) {
			return false;
		}

		return rename( $this->getFilePath(), $filePath );
	}

	/**
	 * Return the font file path.
	 * @return string Font File Path
	 */
	public function getFilePath() {
		return $this->data['file_path'];
	}

	/**
	 * Get embed CSS for this font.
	 */
	public function getCSS(): array {
		$css['font_face'] =
			"@font-face {\n"
			. "font-family: '" . $this->getName() . "';\n"
			. "src: local('" . $this->getName() . "'), "
			. "local('" . str_replace( ' ', '-', $this->getName() ) . "'), "
			. "url('{$this->getUrl()}') format('{$this->getFormat()}');\n"
			. "}";
		$css['style'] = 'font-family: "' . $this->getName() . '";';

		return $css;
	}

	/**
	 * Return the font name.
	 * @return string Font Name
	 */
	public function getName() {
		return $this->data['name'];
	}

	/**
	 * Return the URL to the font file.
	 *
	 * @access    public
	 * @return string Font URL
	 */
	public function getUrl() {
		$ceFontUrl = $this->configFactory->makeConfig('hydracore')->get('CEFontUrl');

		return rtrim( $ceFontUrl, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $this->getFileName();
	}

	/**
	 * Return the format string for this font file type.
	 *
	 * @access    public
	 * @return string File Format
	 */
	public function getFormat() {
		$formats = [
			'woff' => 'woff',
			'ttf' => 'truetype',
			'otf' => 'opentype',
			'eot' => 'embedded-opentype',
			'svg' => 'svg',
		];
		return ( array_key_exists( $this->getFileType(), $formats ) ? $formats[$this->getFileType()]
			: $this->getFileType() );
	}
}
