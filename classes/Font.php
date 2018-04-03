<?php
/**
 * Curse Inc.
 * HydraCore
 * Font Class
 *
 * @author		Alexia E. Smith
 * @copyright	(c) 2014 Curse Inc.
 * @license		All Rights Reserved
 * @package		HydraCore
 * @link		http://www.curse.com/
 *
**/
namespace HydraCore;

class Font {
	/**
	 * Load a new object from a database row.
	 *
	 * @access	public
	 * @param	string	File Name
	 * @param	string	File Path
	 * @return	mixed	New Font Object or False on Error.
	 */
	static public function loadFromFile($file, $path) {
		$font = new Font();
		$font->setFileName($file);
		$type = $font->getFileType();
		if ($type === false) {
			return false;
		} else {
			$font->setName(ucwords(str_replace('.'.$type, '', $font->getFileName())));
		}
		$font->setFilePath($path);

		return $font;
	}

	/**
	 * Return the font name.
	 *
	 * @access	public
	 * @return	string	Font Name
	 */
	public function getName() {
		return $this->data['name'];
	}

	/**
	 * Set the font name.
	 *
	 * @access	public
	 * @param	string	Font Name
	 * @return	void
	 */
	public function setName($name) {
		$this->data['name'] = $name;
	}

	/**
	 * Return the font file name.
	 *
	 * @access	public
	 * @return	string	Font File Name
	 */
	public function getFileName() {
		return $this->data['file_name'];
	}

	/**
	 * Set the font file name.
	 *
	 * @access	public
	 * @param	string	Font File Name
	 * @return	void
	 */
	public function setFileName($fileName) {
		$this->data['file_name'] = $fileName;
	}

	/**
	 * Return the font file path.
	 *
	 * @access	public
	 * @return	string	Font File Path
	 */
	public function getFilePath() {
		return $this->data['file_path'];
	}

	/**
	 * Set the font file path.
	 *
	 * @access	public
	 * @param	string	Font File Path
	 * @return	void
	 */
	public function setFilePath($filePath) {
		$this->data['file_path'] = $filePath;
	}

	/**
	 * Move the file from the current path to a new path.
	 *
	 * @access	public
	 * @param	string	File Folder
	 * @param	string	File Name
	 * @param	boolean	[Optional] Allow file overwriting.
	 * @return	boolean	Success
	 */
	public function moveFile($fileFolder, $fileName, $overwrite = false) {
		if (!is_dir($fileFolder) || !is_writable($fileFolder)) {
			return false;
		}

		$filePath = rtrim($fileFolder, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$fileName;
		if ($overwrite !== true && file_exists($filePath)) {
			return false;
		}

		return rename($this->getFilePath(), $filePath);
	}

	/**
	 * Return the File Type
	 *
	 * @access	public
	 * @return	mixed	String File Type Extension or False for no File Type.
	 */
	public function getFileType() {

		$ceFontTypes = \ConfigFactory::getDefaultInstance()->makeConfig('hydracore')->get('CEFontTypes');

		$lastDot = strrpos($this->data['file_name'], '.');
		if ($lastDot !== false) {
			$type = strtolower(substr($this->data['file_name'], $lastDot + 1));
			if (in_array($type, $ceFontTypes)) {
				return $type;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Return the format string for this font file type.
	 *
	 * @access	public
	 * @return	void
	 */
	public function getFormat() {
		$formats = [
			'woff'	=> 'woff',
			'ttf'	=> 'truetype',
			'otf'	=> 'opentype',
			'eot'	=> 'embedded-opentype',
			'svg'	=> 'svg'
		];
		return (array_key_exists($this->getFileType(), $formats) ? $formats[$this->getFileType()]: $this->getFileType());
	}

	/**
	 * Return the URL to the font file.
	 *
	 * @access	public
	 * @return	void
	 */
	public function getUrl() {

		$ceFontUrl = \ConfigFactory::getDefaultInstance()->makeConfig('hydracore')->get('CEFontUrl');

		return rtrim($ceFontUrl, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$this->getFileName();
	}

	/**
	 * Function Documentation
	 *
	 * @access	public
	 * @return	void
	 */
	public function getCSS() {
		$css['font_face'] = "@font-face {
	font-family: '{$this->getName()}';
	src: local('{$this->getName()}'), local('".str_replace(' ', '-', $this->getName())."'), url({$this->getUrl()}) format('{$this->getFormat()}');
}";
		$css['style'] = 'font-family: "'.$this->getName().'";';

		return $css;
	}
}
?>