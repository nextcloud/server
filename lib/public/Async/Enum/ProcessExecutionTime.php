<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Async\Enum;

enum ProcessExecutionTime: int {
	case NOW = 0;
	case ASAP = 1;
	case LATER = 2;
	case ON_REQUEST = 9;
}
