<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV;

/**
 * App password scopes for calendars and address books
 */
class TokenScopes {
	public const CALENDAR_READ = 'dav:calendar:read';
	public const CALENDAR_WRITE = 'dav:calendar:write';
	public const CONTACTS_READ = 'dav:contacts:read';
	public const CONTACTS_WRITE = 'dav:contacts:write';
}
