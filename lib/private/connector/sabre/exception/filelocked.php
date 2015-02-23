<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Owen Winkler <a_github@midnightcircus.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
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
