<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Enum;

enum ProcessStatus: int {
	case PREP = 0;
	case STANDBY = 1;
	case RUNNING = 2;
	case ERROR = 5;
	case BLOCKER = 7;
	case SUCCESS = 9;
}
