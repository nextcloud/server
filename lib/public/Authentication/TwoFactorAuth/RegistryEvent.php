<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * @since 15.0.0
 * @deprecated 28.0.0 Use TwoFactorProviderForUserRegistered or TwoFactorProviderForUserUnregistered instead
 * @see \OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserRegistered
 * @see \OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserUnregistered
 */
class RegistryEvent extends Event {
	private IProvider $provider;

	private IUser $user;

	/**
	 * @since 15.0.0
	 */
	public function __construct(IProvider $provider, IUser $user) {
		parent::__construct();
		$this->provider = $provider;
		$this->user = $user;
	}

	/**
	 * @since 15.0.0
	 */
	public function getProvider(): IProvider {
		return $this->provider;
	}

	/**
	 * @since 15.0.0
	 */
	public function getUser(): IUser {
		return $this->user;
	}
}
