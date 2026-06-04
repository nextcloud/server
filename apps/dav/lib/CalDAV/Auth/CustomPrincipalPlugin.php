<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Auth;

use Sabre\DAV\Auth\Plugin;

/**
 * Set a custom principal uri to allow public requests to its calendar
 */
class CustomPrincipalPlugin extends Plugin {
	public function setCurrentPrincipal(?string $currentPrincipal): void {
		$this->currentPrincipal = $currentPrincipal;
	}
}
