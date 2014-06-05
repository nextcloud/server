<?php
/**
 * ownCloud
 *
 * @author Owen Winkler
 * @copyright 2013 Owen Winkler <owen@owncloud.com>
 *
 */

class OC_Connector_Sabre_Exception_FileLocked extends \Sabre\DAV\Exception {

	public function __construct($message = "", $code = 0, Exception $previous = null) {
		if($previous instanceof \OCP\Files\LockNotAcquiredException) {
			$message = sprintf('Target file %s is locked by another process.', $previous->path);
		}
		parent::__construct($message, $code, $previous);
	}

	/**
	 * Returns the HTTP status code for this exception
	 *
	 * @return int
	 */
	public function getHTTPCode() {

		return 503;
	}
}
