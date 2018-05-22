<?php

declare(strict_types = 1);

/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OC\Authentication\TwoFactorAuth;

use OC\Authentication\TwoFactorAuth\Db\ProviderUserAssignmentDao;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IUser;

class Registry implements IRegistry {

	/** @var ProviderUserAssignmentDao */
	private $assignmentDao;

	public function __construct(ProviderUserAssignmentDao $assignmentDao) {
		$this->assignmentDao = $assignmentDao;
	}

	public function getProviderStates(IUser $user): array {
		return $this->assignmentDao->getState($user->getUID());
	}

	public function enableProviderFor(IProvider $provider, IUser $user) {
		$this->assignmentDao->persist($provider->getId(), $user->getUID(), 1);
	}

	public function disableProviderFor(IProvider $provider, IUser $user) {
		$this->assignmentDao->persist($provider->getId(), $user->getUID(), 0);
	}

}
