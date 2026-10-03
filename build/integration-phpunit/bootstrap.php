<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

require __DIR__ . '/../../vendor-bin/behat/vendor/autoload.php';
require __DIR__ . '/../../3rdparty/autoload.php';

spl_autoload_register(static function (string $class): void {
	$prefix = 'NextcloudIntegration\\';
	if (!str_starts_with($class, $prefix)) {
		return;
	}

	$path = __DIR__ . '/lib/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
	if (is_file($path)) {
		require $path;
	}
});
