<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Clark Tomlinson <fallen013@gmail.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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


namespace OCA\Encryption;


use OC\Files\View;
use OCA\Encryption\Crypto\Crypt;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserSession;
use OCP\PreConditionNotMetException;

class Util {
	/**
	 * @var View
	 */
	private $files;
	/**
	 * @var Crypt
	 */
	private $crypt;
	/**
	 * @var ILogger
	 */
	private $logger;
	/**
	 * @var bool|IUser
	 */
	private $user;
	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * Util constructor.
	 *
	 * @param View $files
	 * @param Crypt $crypt
	 * @param ILogger $logger
	 * @param IUserSession $userSession
	 * @param IConfig $config
	 */
	public function __construct(View $files,
								Crypt $crypt,
								ILogger $logger,
								IUserSession $userSession,
								IConfig $config
	) {
		$this->files = $files;
		$this->crypt = $crypt;
		$this->logger = $logger;
		$this->user = $userSession && $userSession->isLoggedIn() ? $userSession->getUser() : false;
		$this->config = $config;
	}

	/**
	 * check if recovery key is enabled for user
	 *
	 * @param string $uid
	 * @return bool
	 */
	public function isRecoveryEnabledForUser($uid) {
		$recoveryMode = $this->config->getUserValue($uid,
			'encryption',
			'recoveryEnabled',
			0);

		return ($recoveryMode === '1');
	}

	/**
	 * @param $enabled
	 * @return bool
	 */
	public function setRecoveryForUser($enabled) {
		$value = $enabled ? '1' : '0';

		try {
			$this->config->setUserValue($this->user->getUID(),
				'encryption',
				'recoveryEnabled',
				$value);
			return true;
		} catch (PreConditionNotMetException $e) {
			return false;
		}
	}

	/**
	 * @param string $uid
	 * @return bool
	 */
	public function userHasFiles($uid) {
		return $this->files->file_exists($uid . '/files');
	}


}
