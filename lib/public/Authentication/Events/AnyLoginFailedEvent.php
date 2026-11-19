<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Authentication\Events;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IWebhookCompatibleEvent;

/**
 * Emitted when the authentication fails
 *
 * @since 26.0.0
 * @since 36.0.0 Implements IWebhookCompatibleEvent
 */
class AnyLoginFailedEvent extends Event implements IWebhookCompatibleEvent {
	private string $loginName;
	private ?string $password;

	/**
	 * @since 26.0.0
	 */
	public function __construct(string $loginName, ?string $password) {
		parent::__construct();

		$this->loginName = $loginName;
		$this->password = $password;
	}

	/**
	 * @since 26.0.0
	 * @deprecated 31.0.0 Use getLoginName() instead
	 */
	public function geLoginName(): string {
		return $this->loginName;
	}

	/**
	 * @since 31.0.0
	 */
	public function getLoginName(): string {
		return $this->loginName;
	}

	/**
	 * @since 26.0.0
	 */
	public function getPassword(): ?string {
		return $this->password;
	}

	/**
	 * The attempted password is never part of the payload.
	 *
	 * @since 36.0.0
	 */
	#[\Override]
	public function getWebhookSerializable(): array {
		return [
			'loginName' => $this->loginName,
		];
	}
}
