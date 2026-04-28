<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Principal;

use Sabre\DAVACL;

class ProxyWrite extends \Sabre\CalDAV\Principal\ProxyWrite implements DAVACL\IACL {
	use DAVACL\ACLTrait;

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getOwner() {
		return $this->principalInfo['uri'];
	}
}
