<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core;

/**
 * App password scopes for services that mix every app's data
 */
class TokenScopes {
	public const SEARCH_READ = 'core:search:read';
	public const PEOPLE_READ = 'core:people:read';
}
