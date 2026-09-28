<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Principal;

use Sabre\DAVACL;

class ProxyRead extends \Sabre\CalDAV\Principal\ProxyRead implements DAVACL\IACL {
	use DAVACL\ACLTrait;

	/**
	 * @inheritDoc
	 */
	public function getOwner() {
		return $this->principalInfo['uri'];
	}
}
