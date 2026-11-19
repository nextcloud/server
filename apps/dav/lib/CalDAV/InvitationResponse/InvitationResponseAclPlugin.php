<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\InvitationResponse;

use OCA\DAV\Connector\Sabre\DavAclPlugin;
use OCP\IUserManager;

/**
 * Resolves local users by email address without applying user discovery
 * restrictions, as invitation responses are processed without a user session.
 */
class InvitationResponseAclPlugin extends DavAclPlugin {
	public function __construct(
		private IUserManager $userManager,
	) {
		parent::__construct();
	}

	/**
	 * @param string $uri
	 * @return string|null
	 */
	#[\Override]
	public function getPrincipalByUri($uri) {
		$principal = parent::getPrincipalByUri($uri);
		if ($principal !== null || !str_starts_with($uri, 'mailto:')) {
			return $principal;
		}

		$users = $this->userManager->getByEmail(substr($uri, 7));
		if (count($users) !== 1) {
			return null;
		}

		return 'principals/users/' . $users[0]->getUID();
	}
}
