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
	/**
	 * @experimental 36.0.0
	 */
	case String = 'string';
	/**
	 * @experimental 36.0.0
	 */
	case Integer = 'integer';
	/**
	 * A Unix timestamp.
	 *
	 * @experimental 36.0.0
	 */
	case DateTime = 'datetime';
	/**
	 * @experimental 36.0.0
	 */
	case Boolean = 'boolean';
	/**
	 * A structured value, as an associative array.
	 *
	 * @experimental 36.0.0
	 */
	case Object = 'object';
}
