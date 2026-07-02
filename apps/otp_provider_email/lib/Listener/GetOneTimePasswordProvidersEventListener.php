<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderEmail\Listener;

use OCA\OTPProviderEmail\EmailProvider;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\L10N\IFactory;
use OCP\OneTimePassword\Events\GetOneTimePasswordProvidersEvent;
use OCP\OneTimePassword\IOneTimePasswordProvider;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<GetOneTimePasswordProvidersEvent>
 */
class GetOneTimePasswordProvidersEventListener implements IEventListener {
	private IOneTimePasswordProvider $provider;

	public function __construct(
		IFactory $l10nFactory,
		private LoggerInterface $logger,
	) {
		$this->provider = new EmailProvider($l10nFactory, $this->logger);
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof GetOneTimePasswordProvidersEvent)) {
			return;
		}

		$event->addProvider($this->provider);
	}
}
