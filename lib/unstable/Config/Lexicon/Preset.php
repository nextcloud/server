<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace NCU\Config\Lexicon;

/**
 * list of preset to handle the default behavior of the instance
 *
 * @see ConfigLexiconEntry::preset
 *
 * - **Preset::LARGE** - Large size organisation (> 50k accounts)
 * - **Preset::MEDIUM** - Medium size organisation (> 100 accounts)
 * - **Preset::SMALL** - Small size organisation (< 100 accounts)
 * - **Preset::SHARED** - Shared hosting
 * - **Preset::EDUCATION** - School/University
 * - **Preset::CLUB** - Club/Association
 * - **Preset::FAMILY** - Family
 * - **Preset::PRIVATE** - Private
 *
 * @experimental 32.0.0
 * @deprecated 32.0.0  use \OCP\Config\Lexicon\Preset
 * @see \OCP\Config\Lexicon\Preset
 */
enum Preset: int {
	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0
	 */
	case LARGE = 8;
	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0
	 */
	case MEDIUM = 7;
	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0
	 */
	case SMALL = 6;
	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0
	 */
	case SHARED = 5;
	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0
	 */
	case EDUCATION = 4;
	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0
	 */
	case CLUB = 3;
	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0
	 */
	case FAMILY = 2;
	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0
	 */
	case PRIVATE = 1;
	/**
	 * @experimental 32.0.0
	 * @deprecated 32.0.0
	 */
	case NONE = 0;
}
