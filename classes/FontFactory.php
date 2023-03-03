<?php

declare(strict_types=1);

namespace HydraCore;

use ConfigFactory;

//todo seems that class not in use outside the extension. One usage in \SpecialFontManager
class FontFactory {

	public function __construct( private ConfigFactory $configFactory ) {
	}

	public function loadFromFile( string $file, string $path ): ?Font {
		$font = new Font( $this->configFactory );
		$font->setFileName( $file );
		$type = $font->getFileType();
		if ( $type === false ) {
			return null;
		}
		$font->setName( ucwords( str_replace( '.' . $type, '', $font->getFileName() ) ) );
		$font->setFilePath( $path );

		return $font;
	}
}
