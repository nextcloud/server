<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Sharing\AppInfo;

use NCU\Sharing\Event\SharesDeletedEvent;
use NCU\Sharing\Event\SharesUpdatedEvent;
use OC\Sharing\SharingLegacySync;
use OC\Sharing\SharingManager;
use OCA\Sharing\Capabilities;
use OCA\Sharing\Middleware\ShareApiEnabledMiddleware;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Share\Events\ShareAcceptedEvent;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\Events\ShareDeletedFromSelfEvent;
use OCP\Share\Events\ShareMovedEvent;
use OCP\Share\Events\ShareRestoredEvent;
use OCP\Share\Events\ShareUpdatedEvent;
use OCP\User\Events\BeforeUserDeletedEvent;

final class Application extends App implements IBootstrap {
	public const string APP_ID = 'sharing';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		$context->registerCapability(Capabilities::class);
		$context->registerMiddleware(ShareApiEnabledMiddleware::class);

		$context->registerEventListener(BeforeUserDeletedEvent::class, SharingManager::class);
		$context->registerEventListener(ShareAcceptedEvent::class, SharingLegacySync::class);
		$context->registerEventListener(ShareCreatedEvent::class, SharingLegacySync::class);
		$context->registerEventListener(ShareDeletedEvent::class, SharingLegacySync::class);
		$context->registerEventListener(ShareDeletedFromSelfEvent::class, SharingLegacySync::class);
		$context->registerEventListener(ShareMovedEvent::class, SharingLegacySync::class);
		$context->registerEventListener(ShareRestoredEvent::class, SharingLegacySync::class);
		$context->registerEventListener(ShareUpdatedEvent::class, SharingLegacySync::class);
		$context->registerEventListener(SharesUpdatedEvent::class, SharingLegacySync::class);
		$context->registerEventListener(SharesDeletedEvent::class, SharingLegacySync::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
		// Nothing to do
	}
}
