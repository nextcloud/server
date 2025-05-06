<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async\Enum;

enum BlockType: int {
	case CLOSURE = 1;
	case INVOKABLE = 2;
	case CLASSNAME = 3;
}
