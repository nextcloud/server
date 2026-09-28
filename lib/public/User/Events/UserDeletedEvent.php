<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * @since 18.0.0
 */
class UserDeletedEvent extends Event {
	/** @var IUser */
	private $user;

	/**
	 * @param IUser $user
	 * @since 18.0.0
	 */
	public function __construct(IUser $user) {
		parent::__construct();
		$this->user = $user;
	}

	/**
	 * @return IUser
	 * @since 18.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @since 31.0.0
	 */
	public function getUid(): string {
		return $this->user->getUID();
	}
}
