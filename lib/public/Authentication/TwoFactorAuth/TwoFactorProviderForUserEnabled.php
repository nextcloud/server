<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Authentication\TwoFactorAuth;

use OCP\EventDispatcher\Event;
use OCP\IUser;

/**
 * @since 22.0.0
 * @deprecated 28.0.0 Use \OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed instead
 * @see \OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed
 */
class TwoFactorProviderForUserEnabled extends Event {
	/** @var IProvider */
	private $provider;

	/** @var IUser */
	private $user;

	/**
	 * @since 22.0.0
	 */
	public function __construct(IUser $user, IProvider $provider) {
		$this->user = $user;
		$this->provider = $provider;
	}

	/**
	 * @return IUser
	 * @since 22.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}

	/**
	 * @return IProvider
	 * @since 22.0.0
	 */
	public function getProvider(): IProvider {
		return $this->provider;
	}
}
