<?php


namespace OC\Connector\Sabre\Exception;

class UnsupportedMediaType extends \Sabre\DAV\Exception {

	/**
	 * Returns the HTTP status code for this exception
	 *
	 * @return int
	 */
	public function getHTTPCode() {

		return 415;

	}

}
