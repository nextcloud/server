<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\UserStatus\IUserStatus;

/**
 * @since 20.0.0
 */
class UserLiveStatusEvent extends Event {
	/**
	 * @var string
	 * @since 20.0.0
	 */
	public const STATUS_ONLINE = 'online';

	/**
	 * @var string
	 * @since 20.0.0
	 */
	public const STATUS_AWAY = 'away';

	/**
	 * @var string
	 * @since 20.0.0
	 */
	public const STATUS_OFFLINE = 'offline';

	private IUser $user;
	private string $status;
	private int $timestamp;
	private ?IUserStatus $userStatus = null;

	/**
	 * @since 20.0.0
	 */
	public function __construct(IUser $user,
		string $status,
		int $timestamp) {
		parent::__construct();
		$this->user = $user;
		$this->status = $status;
		$this->timestamp = $timestamp;
	}

	/**
	 * @return IUser
	 * @since 20.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @return string
	 * @since 20.0.0
	 */
	public function getStatus(): string {
		return $this->status;
	}

	/**
	 * @return int
	 * @since 20.0.0
	 */
	public function getTimestamp(): int {
		return $this->timestamp;
	}

	/**
	 * Get the user status that might be available after processing the event
	 * @since 24.0.0
	 */
	public function getUserStatus(): ?IUserStatus {
		return $this->userStatus;
	}

	/**
	 * @since 24.0.0
	 */
	public function setUserStatus(IUserStatus $userStatus) {
		$this->userStatus = $userStatus;
	}
}
