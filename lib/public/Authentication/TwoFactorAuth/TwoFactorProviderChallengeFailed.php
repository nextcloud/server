<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Authentication\TwoFactorAuth;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;
use OCP\EventDispatcher\JsonSerializer;
use OCP\IUser;

/**
 * @since 28.0.0
 * @since 36.0.0 Implements IWebhookCompatibleEvent
 */
class TwoFactorProviderChallengeFailed extends Event implements IWebhookCompatibleEvent {
	/**
	 * @since 28.0.0
	 */
	public function __construct(
		private IUser $user,
		private IProvider $provider,
	) {
		parent::__construct();
	}

	/**
	 * @since 28.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @since 28.0.0
	 */
	public function getProvider(): IProvider {
		return $this->provider;
	}

	/**
	 * @since 36.0.0
	 */
	#[\Override]
	public function getWebhookSerializable(): array {
		return [
			'user' => JsonSerializer::serializeUser($this->user),
			'provider' => [
				'id' => $this->provider->getId(),
				'displayName' => $this->provider->getDisplayName(),
			],
		];
	}
}
