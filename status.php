<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require_once __DIR__ . '/lib/versioncheck.php';

use OC\SystemConfig;
use OCP\Defaults;
use OCP\Server;
use OCP\ServerVersion;
use OCP\Util;
use Psr\Log\LoggerInterface;

try {
	require_once __DIR__ . '/lib/base.php';

	$systemConfig = Server::get(SystemConfig::class);

	$installed = (bool)$systemConfig->getValue('installed', false);
	$maintenance = (bool)$systemConfig->getValue('maintenance', false);
	# see core/lib/private/legacy/defaults.php and core/themes/example/defaults.php
	# for description and defaults
	$defaults = new Defaults();
	$serverVersion = Server::get(ServerVersion::class);
	$values = [
		'installed' => $installed,
		'maintenance' => $maintenance,
		'needsDbUpgrade' => Util::needUpgrade(),
		'version' => implode('.', $serverVersion->getVersion()),
		'versionstring' => $serverVersion->getVersionString(),
		'edition' => '',
		'productname' => $defaults->getProductName(),
		'extendedSupport' => Util::hasExtendedSupport()
	];
	if (OC::$CLI) {
		print_r($values);
	} else {
		header('Access-Control-Allow-Origin: *');
		header('Content-Type: application/json');
		echo json_encode($values);
	}
} catch (Exception $ex) {
	http_response_code(500);
	Server::get(LoggerInterface::class)->error($ex->getMessage(), ['app' => 'remote','exception' => $ex]);
}
