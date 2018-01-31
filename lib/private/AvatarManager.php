<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\IL10N;

/**
 * This class implements methods to access Avatar functionality
 */
class AvatarManager implements IAvatarManager {

	/** @var  IUserManager */
	private $userManager;

	/** @var IAppData */
	private $appData;

	/** @var IL10N */
	private $l;

	/** @var ILogger  */
	private $logger;

	/** @var IConfig */
	private $config;

	/**
	 * AvatarManager constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IAppData $appData
	 * @param IL10N $l
	 * @param ILogger $logger
	 * @param IConfig $config
	 */
	public function __construct(
			IUserManager $userManager,
			IAppData $appData,
			IL10N $l,
			ILogger $logger,
			IConfig $config) {
		$this->userManager = $userManager;
		$this->appData = $appData;
		$this->l = $l;
		$this->logger = $logger;
		$this->config = $config;

		$this->userManager->listen('\OC\User', 'changeUser', function ($user, $feature, $value, $oldValue) {
			if ($feature === 'displayName') {
				$this->updateAvatarVersionOnDisplayNameChange($user);
			}
		});
	}

	/**
	 * return a user specific instance of \OCP\IAvatar
	 * @see \OCP\IAvatar
	 * @param string $userId the ownCloud user id
	 * @return \OCP\IAvatar
	 * @throws \Exception In case the username is potentially dangerous
	 * @throws NotFoundException In case there is no user folder yet
	 */
	public function getAvatar($userId) {
		$user = $this->userManager->get($userId);
		if (is_null($user)) {
			throw new \Exception('user does not exist');
		}

		// sanitize userID - fixes casing issue (needed for the filesystem stuff that is done below)
		$userId = $user->getUID();

		try {
			$folder = $this->appData->getFolder($userId);
		} catch (NotFoundException $e) {
			$folder = $this->appData->newFolder($userId);
		}

		return new Avatar($folder, $this->l, $user, $this->logger, $this->config);
	}

	/**
	 * Increases the avatar version if needed.
	 *
	 * When a user has no avatar set the avatar is generated in the client based
	 * on the display name, so in that case a display name change acts like an
	 * avatar change and its version has to be increased.
	 *
	 * @param \OC\User\User $user the user whose display name has changed
	 */
	private function updateAvatarVersionOnDisplayNameChange(\OC\User\User $user) {
		$avatar = $this->getAvatar($user->getUID());

		if (!$avatar->exists()) {
			$this->config->setUserValue($user->getUID(), 'avatar', 'version',
				(int)$this->config->getUserValue($user->getUID(), 'avatar', 'version', 0) + 1);
		}
	}
}
