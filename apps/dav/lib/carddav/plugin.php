<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\DAV\CardDAV;

use Sabre\HTTP\URLUtil;

class Plugin extends \Sabre\CardDAV\Plugin {

	/**
	 * Returns the addressbook home for a given principal
	 *
	 * @param string $principal
	 * @return string
	 */
	protected function getAddressbookHomeForPrincipal($principal) {

		if (strrpos($principal, 'principals/users', -strlen($principal)) !== FALSE) {
			list(, $principalId) = URLUtil::splitPath($principal);
			return self::ADDRESSBOOK_ROOT . '/users/' . $principalId;
		}
		if (strrpos($principal, 'principals/system', -strlen($principal)) !== FALSE) {
			list(, $principalId) = URLUtil::splitPath($principal);
			return self::ADDRESSBOOK_ROOT . '/system/' . $principalId;
		}

		throw new \LogicException('This is not supposed to happen');
	}
}
