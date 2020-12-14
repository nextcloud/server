<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Daniel Calviño Sánchez (danxuliu@gmail.com)
 *
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Avatar;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\IAvatar;
use OCP\IAvatarProvider;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class UserAvatarProvider implements IAvatarProvider {

	/** @var IUserManager */
	private $userManager;

	/** @var IAppData */
	private $appData;

	/** @var IL10N */
	private $l;

	/** @var LoggerInterface */
	private $logger;

	/** @var IConfig */
	private $config;

	public function __construct(
			IUserManager $userManager,
			IAppData $appData,
			IL10N $l,
			LoggerInterface $logger,
			IConfig $config) {
		$this->userManager = $userManager;
		$this->appData = $appData;
		$this->l = $l;
		$this->logger = $logger;
		$this->config = $config;
	}

	/**
	 * Returns a UserAvatar instance for the given user id
	 *
	 * @param string $id the user id
	 * @returns IAvatar
	 * @throws \Exception In case the username is potentially dangerous
	 * @throws NotFoundException In case there is no user folder yet
	 */
	public function getAvatar(string $id): IAvatar {
		$user = $this->userManager->get($id);
		if ($user === null) {
			throw new \Exception('user does not exist');
		}

		// sanitize userID - fixes casing issue (needed for the filesystem stuff that is done below)
		$userId = $user->getUID();

		try {
			$folder = $this->appData->getFolder($userId);
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder($userId);
		}

		return new UserAvatar($folder, $this->l, $user, $this->logger, $this->config);
	}

	/**
	 * Returns the cache duration for user avatars in seconds
	 *
	 * @param IAvatar $avatar ignored, same duration for all user avatars
	 * @return int|null the cache duration
	 */
	public function getCacheTimeToLive(IAvatar $avatar): ?int {
		// Cache for 1 day
		return 60 * 60 * 24;
	}
}
