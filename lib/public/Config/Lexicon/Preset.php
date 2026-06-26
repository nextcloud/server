<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Config\Lexicon;

use OCP\AppFramework\Attribute\Consumable;

/**
 * list of preset to handle the default behavior of the instance
 *
 * @see Entry::preset
 *
 * - **Preset::LARGE** - Large size organisation (> 50k accounts)
 * - **Preset::MEDIUM** - Medium size organisation (> 100 accounts)
 * - **Preset::SMALL** - Small size organisation (< 100 accounts)
 * - **Preset::SHARED** - Shared hosting
 * - **Preset::UNIVERSITY** - Education, large size
 * - **Preset::SCHOOL** - Eduction, small/medium size
 * - **Preset::CLUB** - Club/Association
 * - **Preset::FAMILY** - Family
 * - **Preset::PRIVATE** - Private
 */
#[Consumable(since: '32.0.0')]
enum Preset: int {
	/** @since 32.0.0 */
	case LARGE = 9;
	/** @since 32.0.0 */
	case MEDIUM = 8;
	/** @since 32.0.0 */
	case SMALL = 7;
	/** @since 32.0.0 */
	case SHARED = 6;
	/** @since 32.0.0 */
	case UNIVERSITY = 5;
	/** @since 32.0.0 */
	case SCHOOL = 4;
	/** @since 32.0.0 */
	case CLUB = 3;
	/** @since 32.0.0 */
	case FAMILY = 2;
	/** @since 32.0.0 */
	case PRIVATE = 1;
	/** @since 32.0.0 */
	case NONE = 0;
}
