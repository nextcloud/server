<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth\Password;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\SessionStorageWrapper;
use OCA\Files_External\Lib\StorageConfig;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\LoginCredentials\IStore as CredentialsStore;
use OCP\Files\Storage\IStorage;
use OCP\Files\StorageAuthException;
use OCP\IL10N;
use OCP\IUser;

/**
 * Username and password from login credentials, saved in session
 */
class SessionCredentials extends AuthMechanism {

	public function __construct(
		IL10N $l,
		private CredentialsStore $credentialsStore,
	) {
		$this->setIdentifier('password::sessioncredentials')
			->setScheme(self::SCHEME_PASSWORD)
			->setText($l->t('Log-in credentials, save in session'))
			->addParameters([
				(new DefinitionParameter('password', $l->t('Password')))
					->setType(DefinitionParameter::VALUE_PASSWORD)
					->setFlag(DefinitionParameter::FLAG_HIDDEN)
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
			]);
	}

	/**
	 * @return void
	 */
	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null) {
		try {
			$credentials = $this->credentialsStore->getLoginCredentials();
		} catch (CredentialsUnavailableException $e) {
			throw new InsufficientDataForMeaningfulAnswerException('No session credentials saved');
		}

		if ($user === null) {
			throw new StorageAuthException('Session unavailable');
		}

		if ($credentials->getUID() !== $user->getUID()) {
			throw new StorageAuthException('Session credentials for storage owner not available');
		}

		$storage->setBackendOption('user', $credentials->getLoginName());
		$storage->setBackendOption('password', $credentials->getPassword());
	}

	public function wrapStorage(IStorage $storage): IStorage {
		return new SessionStorageWrapper(['storage' => $storage]);
	}
}
