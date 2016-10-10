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

use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IAvatarManager;
use OCP\ILogger;
use OCP\IUserManager;
use OCP\Files\IRootFolder;
use OCP\IL10N;

/**
 * This class implements methods to access Avatar functionality
 */
class AvatarManager implements IAvatarManager {

	/** @var  IUserManager */
	private $userManager;

	/** @var  IRootFolder */
	private $rootFolder;

	/** @var IL10N */
	private $l;

	/** @var ILogger  */
	private $logger;

	/**
	 * AvatarManager constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IRootFolder $rootFolder
	 * @param IL10N $l
	 * @param ILogger $logger
	 */
	public function __construct(
			IUserManager $userManager,
			IRootFolder $rootFolder,
			IL10N $l,
			ILogger $logger) {
		$this->userManager = $userManager;
		$this->rootFolder = $rootFolder;
		$this->l = $l;
		$this->logger = $logger;
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

		/*
		 * Fix for #22119
		 * Basically we do not want to copy the skeleton folder
		 */
		\OC\Files\Filesystem::initMountPoints($userId);
		$dir = '/' . $userId;
		/** @var Folder $folder */
		$folder = $this->rootFolder->get($dir);

		return new Avatar($folder, $this->l, $user, $this->logger);
	}
}
