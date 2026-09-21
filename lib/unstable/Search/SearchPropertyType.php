<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Search;

/**
 * The type of the values a {@see SearchPropertyDefinition} is compared against.
 *
 * @experimental 36.0.0
 */
enum SearchPropertyType: string {
	case String = 'string';
	case Integer = 'integer';
	/** A Unix timestamp. */
	case DateTime = 'datetime';
	case Boolean = 'boolean';
	/** A structured value, as an associative array. */
	case Object = 'object';
}
