<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\AppInfo;

use OCA\Sharing\Capabilities;
use OCA\Sharing\Middleware\ShareApiEnabledMiddleware;
use OCA\Sharing\SharingBackend;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Server;
use OCP\Sharing\ISharingRegistry;

final class Application extends App implements IBootstrap {
	public const string APP_ID = 'sharing';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
		$context->registerMiddleware(ShareApiEnabledMiddleware::class);

		$registry = Server::get(ISharingRegistry::class);
		$registry->registerSharingBackend(Server::get(SharingBackend::class));
	}

	#[\Override]
	public function boot(IBootContext $context): void {
		// Nothing to do
	}
}
