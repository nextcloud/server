<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Async\Enum;

enum BlockActivity: int {
	case STARTING = 0;
	case DEBUG = 1;
	case NOTICE = 2;
	case WARNING = 3;
	case ERROR = 4;
	case ENDING = 9;
}
