<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OC\Kernel\ConsoleKernel;

require_once __DIR__ . '/lib/versioncheck.php';
require_once __DIR__ . '/lib/private/Kernel/Kernel.php';
require_once __DIR__ . '/lib/private/Kernel/ConsoleKernel.php';

(new ConsoleKernel())
	->boot()
	->run($_SERVER['argv']);
