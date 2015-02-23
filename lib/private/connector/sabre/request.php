<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Stefan Herbrechtsmeier <stefan@herbrechtsmeier.net>
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
class OC_Connector_Sabre_Request extends \Sabre\HTTP\Request {
	/**
	 * Returns the requested uri
	 *
	 * @return string
	 */
	public function getUri() {
		return \OC::$server->getRequest()->getRequestUri();
	}

	/**
	 * Returns a specific item from the _SERVER array.
	 *
	 * Do not rely on this feature, it is for internal use only.
	 *
	 * @param string $field
	 * @return string
	 */
	public function getRawServerValue($field) {
		if($field == 'REQUEST_URI') {
			return $this->getUri();
		}
		else{
			return isset($this->_SERVER[$field])?$this->_SERVER[$field]:null;
		}
	}
}
