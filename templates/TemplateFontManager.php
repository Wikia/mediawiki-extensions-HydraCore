<?php
/**
 * Curse Inc.
 * HydraCore
 * Font Upload Skin
 *
 * @author		Alexia E. Smith
 * @copyright	(c) 2014 Curse Inc.
 * @license		All Rights Reserved
 * @package		HydraCore
 * @link		http://www.curse.com/
 *
**/

class TemplateFontManager {
	/**
	 * Font Upload Page
	 *
	 * @access	public
	 * @param	array	Array of Font objets.
	 * @param	boolean	Successful Upload
	 * @return	string	Built HTML
	 */
	public function fontManagerPage($fonts, $upload = null) {
		global $wgUser;

		$fontManagerPage = Title::newFromText('Special:FontManager');
		$fontManagerURL = $fontManagerPage->getFullURL();

		$html = '';

		if ($upload !== null) {
			if ($upload === true) {
				$html .= "<div class='successbox'>".wfMessage('font_uploaded')->escaped()."</div>";
			} else {
				$html .= "<div class='errorbox'>".wfMessage('font_not_uploaded')->escaped()."</div>";
			}
		}

		if ($wgUser->isAllowed('font_upload')) {
			$html .= "
		<div class='font_upload'>
			<form id='font_upload' method='post' action='{$fontManagerURL}?action=upload' enctype='multipart/form-data'>
				<fieldset>
					<legend>".wfMessage('select_font_to_upload')->escaped()."</legend>
					<input id='font_file' type='file' name='font_file' accept='application/font'/>
					<input id='overwrite' type='checkbox' name='overwrite' value='1'/><label for='overwrite'>".wfMessage('overwrite_existing')->escaped()."</label><br/>
					<input id='submit' type='submit' value='".wfMessage('upload_font')->escaped()."'/>
				</fieldset>
			</form>
		</div>";
		}
		if (count($fonts)) {
			foreach ($fonts as $font) {
				$html .= "
			<div id='font_".md5($font->getFileName())."' class='font_block'>
				<style type='text/css'>
{$font->getCSS()['font_face']}
				</style>
				<div class='font_name'>{$font->getName()}</div>
				<div class='font_preview' style='{$font->getCSS()['style']}'>".wfMessage('fox')->parse()."</div>
				<div class='font_path'>{$font->getUrl()}</div>
				<div class='font_css'>
					".wfMessage('add_this_selector')->escaped()."
					<pre>{$font->getCSS()['font_face']}</pre>
				</div>
				<div class='font_css'>
					".wfMessage('use_this_style')->escaped()."
					<pre>{$font->getCSS()['style']}</pre>
				</div>
				<div class='font_controls'></div>
			</div>";
			}
		}

		return $html;
	}
}
