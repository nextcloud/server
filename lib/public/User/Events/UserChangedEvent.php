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
class UserChangedEvent extends Event {
	private IUser $user;
	private string $feature;
	/** @var mixed */
	private $value;
	/** @var mixed */
	private $oldValue;

	/**
	 * @since 18.0.0
	 */
	public function __construct(IUser $user,
		string $feature,
		$value,
		$oldValue = null) {
		parent::__construct();
		$this->user = $user;
		$this->feature = $feature;
		$this->value = $value;
		$this->oldValue = $oldValue;
	}

	/**
	 * @return IUser
	 * @since 18.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @return string
	 * @since 18.0.0
	 */
	public function getFeature(): string {
		return $this->feature;
	}

	/**
	 * @return mixed
	 * @since 18.0.0
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * @return mixed
	 * @since 18.0.0
	 */
	public function getOldValue() {
		return $this->oldValue;
	}
}
