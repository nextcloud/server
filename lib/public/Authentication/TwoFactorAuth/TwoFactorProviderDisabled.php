<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\EventDispatcher\Event;

/**
 * @since 20.0.0
 * @deprecated 28.0.0 Use \OCP\Authentication\TwoFactorAuth\TwoFactorProviderUserDeleted instead
 * @see \OCP\Authentication\TwoFactorAuth\TwoFactorProviderUserDeleted
 */
final class TwoFactorProviderDisabled extends Event {
	/** @var string */
	private $providerId;

	/**
	 * @since 20.0.0
	 */
	public function __construct(string $providerId) {
		parent::__construct();
		$this->providerId = $providerId;
	}

	/**
	 * @since 20.0.0
	 */
	public function getProviderId(): string {
		return $this->providerId;
	}
}
