<?php

/**
 * ownCloud
 *
 * @author Stefan Herbrechtsmeier
 * @copyright 2012 Stefan Herbrechtsmeier <stefan@herbrechtsmeier.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class OC_Connector_Sabre_Request extends \Sabre\HTTP\Request {
	/**
	 * Returns the requested uri
	 *
	 * @return string
	 */
	public function getUri() {
		return OC_Request::requestUri();
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
