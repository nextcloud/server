<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\User\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

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

	/** @var IUser */
	private $user;

	/** @var string */
	private $status;

	/** @var int */
	private $timestamp;

	/**
	 * @param IUser $user
	 * @param string $status
	 * @param int $timestamp
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
}
