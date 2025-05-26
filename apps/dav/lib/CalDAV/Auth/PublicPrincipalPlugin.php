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
	public const PUBLIC_PRINCIPAL = 'principals/system/public';

	public function getCurrentPrincipal(): ?string {
		return self::PUBLIC_PRINCIPAL;
	}
}
