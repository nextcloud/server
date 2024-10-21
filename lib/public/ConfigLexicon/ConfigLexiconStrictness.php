<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\ConfigLexicon;

/**
 * Strictness regarding not-listed config keys
 *
 * @since 31.0.0
 */
enum ConfigLexiconStrictness: int {
	/** @since 31.0.0 */
	case IGNORE = 0; // fully ignore
	/** @since 31.0.0 */
	case NOTICE = 2; // ignore and report
	/** @since 31.0.0 */
	case WARNING = 3; // silently block (returns $default) and report
	/** @since 31.0.0 */
	case EXCEPTION = 5; // block (throws exception) and report
}
