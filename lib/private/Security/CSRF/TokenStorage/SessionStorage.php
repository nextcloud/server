<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Security\CSRF\TokenStorage;

use OCP\ISession;

/**
 * Class SessionStorage provides the session storage
 *
 * @package OC\Security\CSRF\TokenStorage
 */
class SessionStorage {
	public function __construct(
		private ISession $session,
	) {
	}

	public function setSession(ISession $session): void {
		$this->session = $session;
	}

	/**
	 * Returns the current token or throws an exception if none is found.
	 *
	 * @throws \Exception
	 */
	public function getToken(): string {
		$token = $this->session->get('requesttoken');
		if (empty($token)) {
			throw new \Exception('Session does not contain a requesttoken');
		}

		return $token;
	}

	/**
	 * Set the valid current token to $value.
	 */
	public function setToken(string $value): void {
		$this->session->set('requesttoken', $value);
	}

	/**
	 * Removes the current token.
	 */
	public function removeToken(): void {
		$this->session->remove('requesttoken');
	}

	/**
	 * Whether the storage has a storage.
	 */
	public function hasToken(): bool {
		return $this->session->exists('requesttoken');
	}
}
