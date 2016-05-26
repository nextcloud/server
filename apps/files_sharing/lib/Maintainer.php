<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Files_Sharing;

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
