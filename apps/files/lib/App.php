<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files;

use OC\NavigationManager;
use OCA\Files\Service\ChunkedUploadConfig;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;
use Psr\Log\LoggerInterface;

class App {
	public static function extendJsConfig($settings): void {
		$appConfig = json_decode($settings['array']['oc_appconfig'], true);

		$appConfig['files'] = [
			'max_chunk_size' => ChunkedUploadConfig::getMaxChunkSize(),
		];

		$settings['array']['oc_appconfig'] = json_encode($appConfig);
	}
}
