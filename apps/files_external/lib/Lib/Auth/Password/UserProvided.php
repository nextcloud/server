<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth\Password;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\Auth\IUserProvided;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Service\BackendService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICredentialsManager;

/**
 * User provided Username and Password
 */
class UserProvided extends AuthMechanism implements IUserProvided {
	public const CREDENTIALS_IDENTIFIER_PREFIX = 'password::userprovided/';

	public function __construct(
		IL10N $l,
		protected ICredentialsManager $credentialsManager,
	) {
		$this
			->setIdentifier('password::userprovided')
			->setVisibility(BackendService::VISIBILITY_ADMIN)
			->setScheme(self::SCHEME_PASSWORD)
			->setText($l->t('Manually entered, store in database'))
			->addParameters([
				(new DefinitionParameter('user', $l->t('Login')))
					->setFlag(DefinitionParameter::FLAG_USER_PROVIDED),
				(new DefinitionParameter('password', $l->t('Password')))
					->setType(DefinitionParameter::VALUE_PASSWORD)
					->setFlag(DefinitionParameter::FLAG_USER_PROVIDED),
			]);
	}

	private function getCredentialsIdentifier($storageId) {
		return self::CREDENTIALS_IDENTIFIER_PREFIX . $storageId;
	}

	public function saveBackendOptions(IUser $user, $mountId, array $options) {
		if ($options['password'] === DefinitionParameter::UNMODIFIED_PLACEHOLDER) {
			$oldCredentials = $this->credentialsManager->retrieve($user->getUID(), $this->getCredentialsIdentifier($mountId));
			$options['password'] = $oldCredentials['password'];
		}

		$this->credentialsManager->store($user->getUID(), $this->getCredentialsIdentifier($mountId), [
			'user' => $options['user'], // explicitly copy the fields we want instead of just passing the entire $options array
			'password' => $options['password'] // this way we prevent users from being able to modify any other field
		]);
	}

	/**
	 * @return void
	 */
	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null) {
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
