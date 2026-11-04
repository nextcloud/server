<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files;

/**
 * App password scopes for files
 */
class TokenScopes {
	public const FILES_READ = 'files:files:read';
	public const FILES_WRITE = 'files:files:write';
	public const DAV_READ = 'files:dav:read';
	public const DAV_WRITE = 'files:dav:write';
}
