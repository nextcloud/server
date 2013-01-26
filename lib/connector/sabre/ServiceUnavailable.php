<?php
/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL3
 */

class Sabre_DAV_Exception_ServiceUnavailable extends Sabre_DAV_Exception {

	/**
	 * Returns the HTTP statuscode for this exception
	 *
	 * @return int
	 */
	public function getHTTPCode() {

		return 503;
	}
}
