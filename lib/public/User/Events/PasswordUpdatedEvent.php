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
 * Emitted when the user password has been updated.
 *
 * @since 18.0.0
 */
class PasswordUpdatedEvent extends Event {
	/** @var IUser */
	private $user;

	/** @var string */
	private $password;

	/** @var string|null */
	private $recoveryPassword;

	/**
	 * @param IUser $user
	 * @param string $password
	 * @param string|null $recoveryPassword
	 * @since 18.0.0
	 */
	public function __construct(IUser $user,
		string $password,
		?string $recoveryPassword = null) {
		parent::__construct();
		$this->user = $user;
		$this->password = $password;
		$this->recoveryPassword = $recoveryPassword;
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

	/**
	 * @return string
	 * @since 18.0.0
	 */
	public function getPassword(): string {
		return $this->password;
	}

	/**
	 * @return string|null
	 * @since 18.0.0
	 */
	public function getRecoveryPassword(): ?string {
		return $this->recoveryPassword;
	}
}
