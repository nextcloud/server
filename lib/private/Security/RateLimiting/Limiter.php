<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OC\Security\RateLimiting;

use OC\Security\Normalizer\IpAddress;
use OC\Security\RateLimiting\Backend\IBackend;
use OC\Security\RateLimiting\Exception\RateLimitExceededException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;

class Limiter {
	/** @var IBackend */
	private $backend;
	/** @var ITimeFactory */
	private $timeFactory;

	/**
	 * @param IUserSession $userSession
	 * @param IRequest $request
	 * @param ITimeFactory $timeFactory
	 * @param IBackend $backend
	 */
	public function __construct(IUserSession $userSession,
								IRequest $request,
								ITimeFactory $timeFactory,
								IBackend $backend) {
		$this->backend = $backend;
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @param string $methodIdentifier
	 * @param string $userIdentifier
	 * @param int $period
	 * @param int $limit
	 * @throws RateLimitExceededException
	 */
	private function register($methodIdentifier,
							  $userIdentifier,
							  $period,
							  $limit) {
		$existingAttempts = $this->backend->getAttempts($methodIdentifier, $userIdentifier, (int)$period);
		if ($existingAttempts >= (int)$limit) {
			throw new RateLimitExceededException();
		}

		$this->backend->registerAttempt($methodIdentifier, $userIdentifier, $this->timeFactory->getTime());
	}

	/**
	 * Registers attempt for an anonymous request
	 *
	 * @param string $identifier
	 * @param int $anonLimit
	 * @param int $anonPeriod
	 * @param string $ip
	 * @throws RateLimitExceededException
	 */
	public function registerAnonRequest($identifier,
										$anonLimit,
										$anonPeriod,
										$ip) {
		$ipSubnet = (new IpAddress($ip))->getSubnet();

		$anonHashIdentifier = hash('sha512', 'anon::' . $identifier . $ipSubnet);
		$this->register($identifier, $anonHashIdentifier, $anonPeriod, $anonLimit);
	}

	/**
	 * Registers attempt for an authenticated request
	 *
	 * @param string $identifier
	 * @param int $userLimit
	 * @param int $userPeriod
	 * @param IUser $user
	 * @throws RateLimitExceededException
	 */
	public function registerUserRequest($identifier,
										$userLimit,
										$userPeriod,
										IUser $user) {
		$userHashIdentifier = hash('sha512', 'user::' . $identifier . $user->getUID());
		$this->register($identifier, $userHashIdentifier, $userPeriod, $userLimit);
	}
}
