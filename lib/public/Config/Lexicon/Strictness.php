<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Config\Lexicon;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Strictness regarding using not-listed config keys
 *
 * - **Strictness::IGNORE** - fully ignore
 * - **Strictness::NOTICE** - ignore and report
 * - **Strictness::WARNING** - silently block (returns $default) and report
 * - **Strictness::EXCEPTION** - block (throws exception) and report
 */
#[Consumable(since: '32.0.0')]
enum Strictness {
	/** @since 32.0.0 */
	case IGNORE; // fully ignore
	/** @since 32.0.0 */
	case NOTICE; // ignore and report
	/** @since 32.0.0 */
	case WARNING; // silently block (returns $default) and report
	/** @since 32.0.0 */
	case EXCEPTION; // block (throws exception) and report
}
