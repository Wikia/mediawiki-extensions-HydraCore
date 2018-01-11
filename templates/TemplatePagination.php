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
	 * @param	object	Page Title
	 * @return	string	Built HTML
	 */
	public function pagination($pagination, Title $title) {
		$arguments = [];
		if (!empty($pagination['extra'])) {
			$arguments = $pagination['extra'];
		}
		$html = '';
		if (isset($pagination['pages']) && count($pagination['pages'])) {
			$html .= "
		<ul class='pagination'>";
			if (isset($pagination['stats'])) {
				$html .= "<li class='pagination_stats'>Page {$pagination['stats']['current_page']} of {$pagination['stats']['pages']}</li>";
			}

			if (count($pagination['pages']) > 1) {
				if ($pagination['first']) {
					$html .= "<li><a href='{$title->getFullURL($arguments + ['st' => $pagination['first']['st']])}'>&laquo;</a></li>";
				}
				foreach ($pagination['pages'] as $page => $info) {
					if ($page > 0) {
						$html .= "<li".($info['selected'] ? " class='selected'" : null)."><a href='{$title->getFullURL($arguments + ['st' => $info['st']])}'>{$page}</a></li>";
					}
				}
				if ($pagination['last']) {
					$html .= "<li><a href='{$title->getFullURL($arguments + ['st' => $pagination['last']['st']])}'>&raquo;</a></li>";
				}
			}
			$html .= "
		</ul>";
		}

		return $html;
	}
}
