<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Lib\Auth\Password;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\BackendService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICredentialsManager;

/**
 * User provided Global Username and Password
 */
class UserGlobalAuth extends AuthMechanism {
	private const CREDENTIALS_IDENTIFIER = 'password::global';

	public function __construct(
		IL10N $l,
		protected ICredentialsManager $credentialsManager,
	) {
		$this
			->setIdentifier('password::global::user')
			->setVisibility(BackendService::VISIBILITY_DEFAULT)
			->setScheme(self::SCHEME_PASSWORD)
			->setText($l->t('Global credentials, manually entered'));
	}

	public function saveBackendOptions(IUser $user, $id, $backendOptions) {
		// backendOptions are set when invoked via Files app
		// but they are not set when invoked via ext storage settings
		if (!isset($backendOptions['user']) && !isset($backendOptions['password'])) {
			return;
		}

		if ($backendOptions['password'] === DefinitionParameter::UNMODIFIED_PLACEHOLDER) {
			$oldCredentials = $this->credentialsManager->retrieve($user->getUID(), self::CREDENTIALS_IDENTIFIER);
			$backendOptions['password'] = $oldCredentials['password'];
		}

		// make sure we're not setting any unexpected keys
		$credentials = [
			'user' => $backendOptions['user'],
			'password' => $backendOptions['password'],
		];
		$this->credentialsManager->store($user->getUID(), self::CREDENTIALS_IDENTIFIER, $credentials);
	}

	/**
	 * @return void
	 */
	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null) {
		if ($user === null) {
			throw new InsufficientDataForMeaningfulAnswerException('No credentials saved');
		}

		$uid = $user->getUID();
		$credentials = $this->credentialsManager->retrieve($uid, self::CREDENTIALS_IDENTIFIER);

		if (is_array($credentials)) {
			$storage->setBackendOption('user', $credentials['user']);
			$storage->setBackendOption('password', $credentials['password']);
		}
	}
}
