<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Auth;

use Sabre\DAV\Auth\Plugin;

/**
 * Defines the public facing principal option
 */
class PublicPrincipalPlugin extends Plugin {
	public function getCurrentPrincipal(): ?string {
		return 'principals/system/public';
	}
}
