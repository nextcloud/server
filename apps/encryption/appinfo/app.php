<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\AppInfo;

\OCP\Util::addscript('encryption', 'encryption');

$encryptionManager = \OC::$server->getEncryptionManager();
$encryptionSystemReady = $encryptionManager->isReady();

/** @var Application $app */
$app = \OC::$server->query(Application::class);
if ($encryptionSystemReady) {
	$app->registerEncryptionModule($encryptionManager);
	$app->registerHooks(\OC::$server->getConfig());
	$app->setUp($encryptionManager);
}
