<?php


namespace OC\Connector\Sabre\Exception;

/**
 * Entity Too Large
 *
 * This exception is thrown whenever a user tries to upload a file which exceeds hard limitations
 *
 */
class EntityTooLarge extends \Sabre\DAV\Exception {

	/**
	 * Returns the HTTP status code for this exception
	 *
	 * @return int
	 */
	public function getHTTPCode() {

		return 413;

	}

}
