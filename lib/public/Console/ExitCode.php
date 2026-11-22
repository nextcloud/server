<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCP\Console;

/**
 * Exit code of a command
 *
 * @since 35.0.0
 */
enum ExitCode: int {
	/**
	 * @since 35.0.0
	 */
	case Success = 0;
	/**
	 * @since 35.0.0
	 */
	case Failure = 1;
	/**
	 * @since 35.0.0
	 */
	case Invalid = 2;
}
