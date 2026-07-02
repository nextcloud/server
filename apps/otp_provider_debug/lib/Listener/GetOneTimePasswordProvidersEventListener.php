<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderDebug\Listener;

use OCA\OTPProviderDebug\OTPProvider;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\OneTimePassword\Events\GetOneTimePasswordProvidersEvent;
use OCP\OneTimePassword\IOneTimePasswordProvider;
use Psr\Log\LoggerInterface;
use OCP\L10N\IFactory;

/**
 * @template-implements IEventListener<GetOneTimePasswordProvidersEvent>
 */
readonly class GetOneTimePasswordProvidersEventListener implements IEventListener {
	private IOneTimePasswordProvider $provider;

	public function __construct(IFactory $l10nFactory, private LoggerInterface $logger) {
		$this->provider = new OTPProvider($l10nFactory, $logger);
	}

	public function handle(Event $event): void {
		$this->logger->warning('handling GetOneTimePasswordProvidersEvent');

		if (!($event instanceof GetOneTimePasswordProvidersEvent)) {
			return;
		}

		$event->addProvider($this->provider);
	}
}
