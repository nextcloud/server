<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace OCA\Files_External\Lib\Auth\Password;

use OCP\IL10N;
use OCP\IUser;
use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\StorageConfig;
use OCP\Security\ICredentialsManager;
use OCP\Files\Storage;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;

/**
 * User provided Username and Password
 */
class UserProvided extends AuthMechanism {

	const CREDENTIALS_IDENTIFIER_PREFIX = 'password::userprovided/';

	/** @var ICredentialsManager */
	protected $credentialsManager;

	public function __construct(IL10N $l, ICredentialsManager $credentialsManager) {
		$this->credentialsManager = $credentialsManager;

		$this
			->setIdentifier('password::userprovided')
			->setScheme(self::SCHEME_PASSWORD)
			->setText($l->t('User provided'))
			->addParameters([]);
	}

	private function getCredentialsIdentifier($storageId) {
		return self::CREDENTIALS_IDENTIFIER_PREFIX . $storageId;
	}

	public function saveCredentials(IUser $user, $id, $username, $password) {
		$this->credentialsManager->store($user->getUID(), $this->getCredentialsIdentifier($id), [
			'user' => $username,
			'password' => $password
		]);
	}

	public function manipulateStorageConfig(StorageConfig &$storage, IUser $user = null) {
		if (!isset($user)) {
			throw new InsufficientDataForMeaningfulAnswerException('No credentials saved');
		}
		$uid = $user->getUID();
		$credentials = $this->credentialsManager->retrieve($uid, $this->getCredentialsIdentifier($storage->getId()));

		if (!isset($credentials)) {
			throw new InsufficientDataForMeaningfulAnswerException('No credentials saved');
		}

		$storage->setBackendOption('user', $credentials['user']);
		$storage->setBackendOption('password', $credentials['password']);
	}

}
