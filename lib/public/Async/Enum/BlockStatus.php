<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Async\Enum;

enum BlockStatus: int {
	case PREP = 0;
	case STANDBY = 1;
	case RUNNING = 4;

	case BLOCKER = 7;
	case ERROR = 8;
	case SUCCESS = 9;
}
