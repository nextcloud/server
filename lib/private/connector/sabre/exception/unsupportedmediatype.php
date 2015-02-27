<?php


namespace OC\Connector\Sabre\Exception;

/**
 * Unsupported Media Type
 *
 * This exception is thrown whenever a user tries to upload a file which holds content which is not allowed
 *
 */
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
