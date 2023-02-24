<?php
declare( strict_types=1 );

use HydraCore\Font;
use MediaWiki\MediaWikiServices;

return [
	HydraCore::class => static function ( MediaWikiServices $services ): HydraCore {
		return new HydraCore(
			$services->getMainWANObjectCache(),
			$services->getDBLoadBalancer(),
		);
	},
	Font::class => static function ( MediaWikiServices $services ): Font {
		return new Font(
			$services->getConfigFactory(),
		);
	},
];
