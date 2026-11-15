<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\DAV\Sharing;

use Sabre\DAV\Server;
use Sabre\DAVACL\Plugin as AclPlugin;

/**
 * @property Server $server
 */
trait CanChangeSharingTrait {
	/**
	 * Whether the current user may change the sharing or publishing state of the node at $path.
	 * Without an ACL plugin everything is allowed.
	 */
	private function canChangeSharing(string $path, bool $throwExceptions = false): bool {
		$acl = $this->server->getPlugin('acl');
		if (!$acl instanceof AclPlugin) {
			return true;
		}
		return $acl->checkPrivileges($path, '{DAV:}write-acl', AclPlugin::R_PARENT, $throwExceptions);
	}
}
