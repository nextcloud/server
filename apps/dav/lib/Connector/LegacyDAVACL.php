<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector;

use OCA\DAV\Connector\Sabre\DavAclPlugin;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAVACL\Xml\Property\Principal;

class LegacyDAVACL extends DavAclPlugin {

	/**
	 * @inheritdoc
	 */
	public function getCurrentUserPrincipals() {
		$principalV2 = $this->getCurrentUserPrincipal();

		if (is_null($principalV2)) {
			return [];
		}

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
		[, $name] = \Sabre\Uri\split($principal);
		if ($toV2) {
			return "principals/users/$name";
		}
		return "principals/$name";
	}

	public function propFind(PropFind $propFind, INode $node) {
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
