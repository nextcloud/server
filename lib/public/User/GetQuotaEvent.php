<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * Event to allow apps to
 *
 * @since 20.0.0
 */
class GetQuotaEvent extends Event {
	/** @var IUser */
	private $user;
	/** @var string|null */
	private $quota = null;

	/**
	 * @since 20.0.0
	 */
	public function __construct(IUser $user) {
		parent::__construct();
		$this->user = $user;
	}

	/**
	 * @since 20.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * Get the set quota as human readable string, or null if no overwrite is set
	 *
	 * @since 20.0.0
	 */
	public function getQuota(): ?string {
		return $this->quota;
	}

	/**
	 * Set the quota overwrite as human readable string
	 *
	 * @since 20.0.0
	 */
	public function setQuota(string $quota): void {
		$this->quota = $quota;
	}
}
