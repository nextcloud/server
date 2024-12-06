<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace NCU\Config\Lexicon;

/**
 * Strictness regarding using not-listed config keys
 *
 * - **ConfigLexiconStrictness::IGNORE** - fully ignore
 * - **ConfigLexiconStrictness::NOTICE** - ignore and report
 * - **ConfigLexiconStrictness::WARNING** - silently block (returns $default) and report
 * - **ConfigLexiconStrictness::EXCEPTION** - block (throws exception) and report
 *
 * @experimental 31.0.0
 */
enum ConfigLexiconStrictness: int {
	/** @experimental 31.0.0 */
	case IGNORE = 0; // fully ignore
	/** @experimental 31.0.0 */
	case NOTICE = 2; // ignore and report
	/** @experimental 31.0.0 */
	case WARNING = 3; // silently block (returns $default) and report
	/** @experimental 31.0.0 */
	case EXCEPTION = 5; // block (throws exception) and report
}
