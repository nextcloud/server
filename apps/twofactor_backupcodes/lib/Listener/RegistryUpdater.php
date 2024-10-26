<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Listener;

use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCA\TwoFactorBackupCodes\Provider\BackupCodesProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<CodesGenerated> */
class RegistryUpdater implements IEventListener {

	public function __construct(
		private IRegistry $registry,
		private BackupCodesProvider $provider,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof CodesGenerated) {
			$this->registry->enableProviderFor($this->provider, $event->getUser());
		}
	}
}
