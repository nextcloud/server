<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderDebug\Listener;

use OCA\OTPProviderDebug\AppInfo\Application;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\OneTimePassword\Events\SendOneTimePasswordEvent;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<SendOneTimePasswordEvent>
 */
readonly class SendOneTimePasswordEventListener implements IEventListener {

	public function __construct(private LoggerInterface $logger) { }

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof SendOneTimePasswordEvent) || $event->getProvider() !== Application::OTP_PROVIDER_ID) {
			return;
		}

		$pw = $event->getPassword();
		$rec = $event->getRecipient();
		$this->logger->info("OTP password sent: '$pw'");
		$event->markConsumed();
		$event->setMessage("OTP was sent to recipient '$rec'");
	}
}
