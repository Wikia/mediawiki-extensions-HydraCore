<?php
declare( strict_types=1 );

use HydraCore\Font;
use HydraCore\FontFactory;
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

	FontFactory::class => static function ( MediaWikiServices $services ): FontFactory {
		return new FontFactory(
			$services->getConfigFactory(),
		);
	},
];
