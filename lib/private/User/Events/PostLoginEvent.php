<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\User\Events;

use OCP\EventDispatcher\Event;
use OCP\IUser;

class PostLoginEvent extends Event {

	/** @var IUser */
	private $user;
	/** @var string */
	private $password;
	/** @var bool */
	private $isTokenLogin;


	public function __construct(IUser $user, string $password, bool $isTokenLogin) {
		parent::__construct();

		$this->user = $user;
		$this->password = $password;
		$this->isTokenLogin = $isTokenLogin;
	}

	public function getUser(): IUser {
		return $this->user;
	}

	public function hasPassword(): bool {
		return $this->password !== '';
	}

	public function getPassword(): string {
		return $this->password;
	}

	public function getIsTokenLogin(): bool {
		return $this->isTokenLogin;
	}
}
