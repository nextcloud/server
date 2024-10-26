<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Listener;

use OCA\TwoFactorBackupCodes\BackgroundJob\RememberBackupCodesJob;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserUnregistered;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<TwoFactorProviderForUserUnregistered> */
class ProviderDisabled implements IEventListener {

	public function __construct(
		private IRegistry $registry,
		private IJobList $jobList,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof TwoFactorProviderForUserUnregistered)) {
			return;
		}

		$providers = $this->registry->getProviderStates($event->getUser());

		// Loop over all providers. If all are disabled we remove the job
		$state = array_reduce($providers, function (bool $carry, bool $enabled) {
			return $carry || $enabled;
		}, false);

		if ($state === false) {
			$this->jobList->remove(RememberBackupCodesJob::class, ['uid' => $event->getUser()->getUID()]);
		}
	}
}
