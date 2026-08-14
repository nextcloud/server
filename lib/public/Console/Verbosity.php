<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console;

/**
 * Verbosity of a command
 *
 * @since 35.0.0
 */
enum Verbosity: int {
	/** @since 35.0.0 */
	case Quiet = 16;
	/** @since 35.0.0 */
	case Normal = 32;
	/** @since 35.0.0 */
	case Verbose = 64;
	/** @since 35.0.0 */
	case VeryVerbose = 128;
	/** @since 35.0.0 */
	case Debug = 256;
}
