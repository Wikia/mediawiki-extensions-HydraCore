<?php

/**
 * Subclass of the built-in hidden field class that allows values to be set by request
 * (normal hidden fields are always hard-coded to the default value)
 */
class HTMLDynamicHiddenField extends HTMLHiddenField {
	public function getTableRow( $value ) {
		$params = [];
		if ( $this->mID ) {
			$params['id'] = $this->mID;
		}

		$this->mParent->addHiddenField( $this->mName, $value, $params );

		return '';
	}
}
