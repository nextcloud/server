<?php
/**
 * ownCloud
 *
 * @author Morris Jobke
 * @copyright 2013 Morris Jobke morris.jobke@gmail.com
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
 */

namespace OCA\Files\Share;

/**
 * Maintains stuff around the sharing functionality
 *
 * for example: on disable of "allow links" it removes all link shares
 */

class Maintainer {

	/**
	 * Keeps track of the "allow links" config setting
	 * and removes all link shares if the config option is set to "no"
	 *
	 * @param array $params array with app, key, value as named values
	 */
	static public function configChangeHook($params) {
		if($params['app'] === 'core' && $params['key'] === 'shareapi_allow_links' && $params['value'] === 'no') {
			\OCP\Share::removeAllLinkShares();
		}
	}

}
