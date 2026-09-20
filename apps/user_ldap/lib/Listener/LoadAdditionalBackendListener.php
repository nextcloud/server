<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Listener;

use OCA\Files_External\Event\LoadAdditionalBackendEvent;
use OCA\Files_External\Service\BackendService;
use OCA\User_LDAP\Handler\ExtStorageConfigHandler;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Override;
use Psr\Container\ContainerInterface;

/**
 * @template-implements IEventListener<LoadAdditionalBackendEvent>
 */
class LoadAdditionalBackendListener implements IEventListener {
	public function __construct(
		private readonly BackendService $storagesBackendService,
		private readonly ContainerInterface $container,
	) {
	}

	#[Override]
	public function handle(Event $event): void {
		if (!$event instanceof LoadAdditionalBackendEvent) {
			return;
		}

		$this->storagesBackendService->registerConfigHandler('home', fn () => $this->container->get(ExtStorageConfigHandler::class));
	}
}
