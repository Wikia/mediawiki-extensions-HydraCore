<?php
declare( strict_types=1 );

use MediaWiki\MediaWikiServices;

return [
	HydraCore::class => static function ( MediaWikiServices $services ): HydraCore {
		return new HydraCore();
	},
];
