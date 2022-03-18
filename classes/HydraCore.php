<?php

use MediaWiki\MediaWikiServices;

/**
 * Collection of basic utility functions
 * @author Noah Manneschmidt
 */

class HydraCore {
	/**
	 * Inserts new elements into a string indexed array at a specific point
	 * Key collisions will mess stuff up
	 * If the target key does not exist, content will be inserted at the end
	 *
	 * @param array    will be modified with new content inserted
	 * @param mixed    string or int before which the new content will be inserted
	 * @param array    an array containing content to insert at the new index
	 * @return void
	 */
	public static function array_insert_before_key( &$target, $targetKey, $insertContent ) {
		$insertPoint = array_search( $targetKey, array_keys( $target ) );
		$target = array_merge(
			array_slice( $target, 0, $insertPoint ),
			$insertContent,
			array_slice( $target, $insertPoint )
		);
	}

	/**
	 * Recursively extracts all keys from nested arrays and returns an array with keys grouped by depth
	 *   for example, this: [
	 *          a => [
	 *                c => [
	 *                      f => []
	 *                ]
	 *          ],
	 *          b => [
	 *                d => [],
	 *                e => []
	 *          ]
	 *      ]
	 *   will return: [
	 *     0 => [a, b],
	 *     1 => [c, d, e],
	 *     2 => [f]
	 *   ]
	 *
	 * @param array
	 * @param int    [optional] maximum depth to recurse (default: 10)
	 * @return array
	 */
	public static function array_keys_recursive( $target, $maxDepth = 10 ) {
		$allKeys = [];
		self::collect_keys_recursive( $target, $allKeys, 0, $maxDepth );
		return $allKeys;
	}

	/**
	 * Helper function for array_keys_recursive. Inserts found keys into $result at appropriate depth index.
	 *
	 * @param array  from which keys will be extracted
	 * @param array  to which found keys will be saved
	 * @param int    current depth at which the function is operating
	 * @param int    maximum depth to which the funciton should recurse
	 * @return void
	 */
	private static function collect_keys_recursive( $target, &$result, $depth, $maxDepth ) {
		// nothing to do if we are at the bottom
		if ( $depth > $maxDepth || empty( $target ) ) {
			return $result;
		}

		// get keys at current depth
		if ( !isset( $result[$depth] ) ) {
			$result[$depth] = [];
		}
		$result[$depth] = array_merge( $result[$depth], array_keys( $target ) );

		// get keys at farther depths
		foreach ( $target as $child ) {
			if ( is_array( $child ) ) {
				self::collect_keys_recursive( $child, $result, $depth + 1, $maxDepth );
			}
		}
	}

	/**
	 * Returns an HTML fragment to display an icon from Font Awesome.
	 * Find an icon you like http://fortawesome.github.io/Font-Awesome/icons/ then pass its name to this fuction.
	 * Icons will only display when the module "ext.hydraCore.font-awesome.styles" is included on the page.
	 *
	 * @param string  name of the icon to use
	 * @param array   extra classes to add to the element
	 * @return string html fragment
	 */
	public static function awesomeIcon( $name, array $extraClasses = [], array $extraAttribs = [] ) {
		if ( count( $extraClasses ) ) {
			$name .= ' ' . implode( ' ', $extraClasses );
		}
		$extraAttribs['class'] = 'fa fa-' . $name;
		return Html::element( 'span', $extraAttribs );
	}

	/**
	 * Returns the number of users who have made at least one edit on the wiki.
	 */
	public static function numberofcontributors() {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$key = $cache->makeKey( 'NumberOfContributors' );
		$hit = $cache->get( $key );
		if ( !$hit ) {
			if ( class_exists( \ActorMigration::class ) ) {
				$actorQuery = \ActorMigration::newMigration()->getJoin( 'rev_user' );
				$userField = $actorQuery['fields']['rev_user'];
			} else {
				$actorQuery = [ 'tables' => [], 'joins' => [] ];
				$userField = 'rev_user';
			}

			$db = wfGetDB( DB_REPLICA );
			$hit = $db->selectField(
				[ 'revision' ] + $actorQuery['tables'],
				"count(distinct $userField)",
				'',
				__METHOD__,
				[],
				$actorQuery['joins']
			);
			$cache->set( $key, $hit, 3600 );
		}
		return $hit;
	}

	/**
	 * Helper function that returns generatePagination() already formatted in the default pagination template.
	 * @param integer    Total number of items to be paginated.
	 * @param integer    [Optional] How many items to display per page.
	 * @param integer    [Optional] Start Position
	 * @param integer    [Optional] Number of extra page numbers to show.
	 * @param array    [Optional] Array of extra URL arguments to append to pagination URLs.
	 * @param string    [Optional] Base URL to use.
	 * @param boolean    [Optional] Show item range next to pagination.
	 * @return string Built Pagination HTML
	 */
	public static function generatePaginationHtml(
		Title $title,
		int $totalItems,
		int $itemsPerPage = 100,
		int $itemStart = 0,
		int $extraPages = 4,
		array $extraArguments = [],
		bool $showTotal = true
	): string {
		$pagination = self::generatePagination( $totalItems, $itemsPerPage, $itemStart, $extraPages );
		$pagination['extra'] = $extraArguments;
		$pagination['showTotal'] = $showTotal;
		$templates = new TemplatePagination();
		return $templates->pagination( $pagination, $title );
	}

	/**
	 * Generates page numbers.
	 * Call this function directly if a custom pagination template is required otherwise use generatePaginationHtml().
	 *
	 * @param integer    Total number of items to be paginated.
	 * @param integer    How many items to display per page.
	 * @param integer    Start Position
	 * @param integer    Number of extra page numbers to show.
	 * @return array Generated array of pagination information.
	 */
	public static function generatePagination( $totalItems, $itemsPerPage = 100, $itemStart = 0, $extraPages = 4 ) {
		if ( $totalItems < 1 ) {
			throw new InvalidArgumentException( 'No items provided' );
		}

		$currentPage = floor( $itemStart / $itemsPerPage ) + 1;
		$totalPages = ceil( $totalItems / $itemsPerPage );
		$lastStart = floor( $totalItems / $itemsPerPage ) * $itemsPerPage;

		$pagination['first'] = [ 'st' => 0, 'selected' => false ];
		$pagination['last'] = [ 'st' => $lastStart, 'selected' => false ];
		$itemsEnd = $itemStart +
					( $itemsPerPage -
					  ( $currentPage * $itemsPerPage - min( $currentPage * $itemsPerPage, $totalItems ) )
					);
		$pagination['stats'] = [
			'pages' => $totalPages,
			'total' => $totalItems,
			'current_page' => $currentPage,
			'items_start' => $itemStart + 1,
			'items_end' => $itemsEnd,
		];

		$pageStart = min( $currentPage, $currentPage - ( $extraPages / 2 ) );
		$pageEnd = min( $totalPages, $currentPage + ( $extraPages / 2 ) );

		if ( $pageStart <= 1 ) {
			$pageStart = 1;
			$pageEnd = $pageStart + $extraPages;
		}
		if ( $pageEnd >= $totalPages ) {
			$pageEnd = $totalPages;
			$pageStart =
				max(
					$pageEnd - $extraPages,
					( $currentPage - ( $extraPages / 2 ) ) - ( ( $extraPages / 2 ) - ( $pageEnd - $currentPage ) )
				);
		}

		for ( $i = $pageStart; $i <= $pageEnd; $i++ ) {
			if ( $i > 0 ) {
				$pagination['pages'][$i] =
					[
						'st' => ( $i * $itemsPerPage ) - $itemsPerPage,
						'selected' => $i == $currentPage,
					];
			}
		}

		return $pagination;
	}

	/**
	 * The real check if we are using a mobile skin
	 */
	public static function isMobileSkin( Skin $skin ): bool {
		return $skin->getSkinName() == 'minerva' || $skin->getSkinName() == 'fandommobile';
	}
}
