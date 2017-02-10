<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\DAV\Connector;

use OCA\DAV\Connector\Sabre\DavAclPlugin;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\HTTP\URLUtil;
use Sabre\DAVACL\Xml\Property\Principal;

class LegacyDAVACL extends DavAclPlugin {

	/**
	 * Converts the v1 principal `principal/<username>` to the new v2
	 * `principal/users/<username>` which is required for permission checks
	 *
	 * @inheritdoc
	 */
	function getCurrentUserPrincipal() {
		$principalV1 = parent::getCurrentUserPrincipal();
		if (is_null($principalV1)) {
			return $principalV1;
		}
		return $this->convertPrincipal($principalV1, true);
	}


	/**
	 * @inheritdoc
	 */
	function getCurrentUserPrincipals() {
		$principalV2 = $this->getCurrentUserPrincipal();

		if (is_null($principalV2)) return [];

		$principalV1 = $this->convertPrincipal($principalV2, false);
		return array_merge(
			[
				$principalV2,
				$principalV1
			],
			$this->getPrincipalMembership($principalV1)
		);
	}

	private function convertPrincipal($principal, $toV2) {
		list(, $name) = URLUtil::splitPath($principal);
		if ($toV2) {
			return "principals/users/$name";
		}
		return "principals/$name";
	}

	function propFind(PropFind $propFind, INode $node) {
		/* Overload current-user-principal */
		$propFind->handle('{DAV:}current-user-principal', function () {
			if ($url = parent::getCurrentUserPrincipal()) {
				return new Principal(Principal::HREF, $url . '/');
			} else {
				return new Principal(Principal::UNAUTHENTICATED);
			}
		});

		return parent::propFind($propFind, $node);
	}
}
