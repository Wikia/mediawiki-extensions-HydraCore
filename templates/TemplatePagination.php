<?php
/**
 * Curse Inc.
 * HydraCore
 * Pagination Template
 * Taken from the defunct Mouse Framework
 *
 * @author 		Alexia E. Smith
 * @copyright	(c) 2010 - 2014 NoName Studios
 * @license		GPLv3
 *
**/

class TemplatePagination {
	/**
	 * Generates pagination template.
	 *
	 * @access	public
	 * @param	array	Array of pagination information.
	 * @return	string	Built HTML
	 */
	public function pagination($pagination) {
		$extra = '';
		if (!empty($pagination['extra'])) {
			$extra = '&'.$pagination['extra'];
		}
		$HTML = '';
		if (isset($pagination['pages']) && count($pagination['pages'])) {
$HTML .= "
	<ul class='pagination'>";
			if (isset($pagination['stats'])) {
				$HTML .= "<li class='pagination_stats'>Page {$pagination['stats']['current_page']} of {$pagination['stats']['pages']}</li>";
			}

			if (count($pagination['pages']) > 1) {
				if ($pagination['first']) {
					$HTML .= "<li><a href='?st={$pagination['first']['st']}{$extra}'>&laquo;</a></li>";
				}
				foreach ($pagination['pages'] as $page => $info) {
					if ($page > 0) {
						$HTML .= "<li".($info['selected'] ? " class='selected'" : null)."><a href='?st={$info['st']}{$extra}'>{$page}</a></li>";
					}
				}
				if ($pagination['last']) {
					$HTML .= "<li><a href='?st={$pagination['last']['st']}{$extra}'>&raquo;</a></li>";
				}
			}
$HTML .= "
	</ul>";
		}

		return $HTML;
	}
}
