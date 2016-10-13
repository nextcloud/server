<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
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
namespace OCA\DAV\Files;

use Sabre\DAVACL\AbstractPrincipalCollection;
use Sabre\HTTP\URLUtil;
use Sabre\DAV\SimpleCollection;

class RootCollection extends AbstractPrincipalCollection {

	/**
	 * This method returns a node for a principal.
	 *
	 * The passed array contains principal information, and is guaranteed to
	 * at least contain a uri item. Other properties may or may not be
	 * supplied by the authentication backend.
	 *
	 * @param array $principalInfo
	 * @return INode
	 */
	function getChildForPrincipal(array $principalInfo) {
		list(,$name) = URLUtil::splitPath($principalInfo['uri']);
		$user = \OC::$server->getUserSession()->getUser();
		if (is_null($user) || $name !== $user->getUID()) {
			// a user is only allowed to see their own home contents, so in case another collection
			// is accessed, we return a simple empty collection for now
			// in the future this could be considered to be used for accessing shared files
			return new SimpleCollection($name);
		}
		return new FilesHome($principalInfo);
	}

	function getName() {
		return 'files';
	}

}
