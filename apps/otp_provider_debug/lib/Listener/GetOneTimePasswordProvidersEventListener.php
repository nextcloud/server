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
use OCP\L10N\IFactory;

/**
 * @template-implements IEventListener<GetOneTimePasswordProvidersEvent>
 */
readonly class GetOneTimePasswordProvidersEventListener implements IEventListener {
	private IOneTimePasswordProvider $provider;

	public function __construct(IFactory $l10nFactory) {
		$this->provider = new OTPProvider($l10nFactory);
	}

	public function handle(Event $event): void {

		if (!($event instanceof GetOneTimePasswordProvidersEvent)) {
			return;
		}

		$event->addProvider($this->provider);
	}
}
