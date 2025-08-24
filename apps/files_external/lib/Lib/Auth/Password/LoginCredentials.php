<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Auth\Password;

use OCA\Files_External\Lib\Auth\AuthMechanism;
use OCA\Files_External\Lib\DefinitionParameter;
use OCA\Files_External\Lib\InsufficientDataForMeaningfulAnswerException;
use OCA\Files_External\Lib\StorageConfig;
use OCA\Files_External\Listener\StorePasswordListener;
use OCP\Authentication\Exceptions\CredentialsUnavailableException;
use OCP\Authentication\LoginCredentials\IStore as CredentialsStore;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;
use OCP\ISession;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\LDAP\ILDAPProviderFactory;
use OCP\Security\ICredentialsManager;
use OCP\User\Events\PasswordUpdatedEvent;
use OCP\User\Events\UserLoggedInEvent;

/**
 * Username and password from login credentials, saved in DB
 */
class LoginCredentials extends AuthMechanism {
	public const CREDENTIALS_IDENTIFIER = 'password::logincredentials/credentials';

	public function __construct(
		IL10N $l,
		protected ISession $session,
		protected ICredentialsManager $credentialsManager,
		private CredentialsStore $credentialsStore,
		IEventDispatcher $eventDispatcher,
		private ILDAPProviderFactory $ldapFactory,
	) {
		$this
			->setIdentifier('password::logincredentials')
			->setScheme(self::SCHEME_PASSWORD)
			->setText($l->t('Log-in credentials, save in database'))
			->addParameters([
				(new DefinitionParameter('password', $l->t('Password')))
					->setType(DefinitionParameter::VALUE_PASSWORD)
					->setFlag(DefinitionParameter::FLAG_HIDDEN)
					->setFlag(DefinitionParameter::FLAG_OPTIONAL),
			]);

		$eventDispatcher->addServiceListener(UserLoggedInEvent::class, StorePasswordListener::class);
		$eventDispatcher->addServiceListener(PasswordUpdatedEvent::class, StorePasswordListener::class);
	}

	private function getCredentials(IUser $user): array {
		$credentials = $this->credentialsManager->retrieve($user->getUID(), self::CREDENTIALS_IDENTIFIER);

		if (is_null($credentials)) {
			// nothing saved in db, try to get it from the session and save it
			try {
				$sessionCredentials = $this->credentialsStore->getLoginCredentials();

				if ($sessionCredentials->getUID() !== $user->getUID()) {
					// Can't take the credentials from the session as they are not the same user
					throw new CredentialsUnavailableException();
				}

				$credentials = [
					'user' => $sessionCredentials->getLoginName(),
					'password' => $sessionCredentials->getPassword(),
				];

				$this->credentialsManager->store($user->getUID(), self::CREDENTIALS_IDENTIFIER, $credentials);
			} catch (CredentialsUnavailableException $e) {
				throw new InsufficientDataForMeaningfulAnswerException('No login credentials saved');
			}
		}

		return $credentials;
	}

	/**
	 * @return void
	 */
	public function manipulateStorageConfig(StorageConfig &$storage, ?IUser $user = null) {
		if (!isset($user)) {
			throw new InsufficientDataForMeaningfulAnswerException('No login credentials saved');
		}
		$credentials = $this->getCredentials($user);

		$loginKey = $storage->getBackendOption('login_ldap_attr');
		if ($loginKey) {
			$backend = $user->getBackend();
			if ($backend instanceof IUserBackend && $backend->getBackendName() === 'LDAP') {
				$value = $this->getLdapPropertyForUser($user, $loginKey);
				if ($value === null) {
					throw new InsufficientDataForMeaningfulAnswerException('Custom ldap attribute not set for user ' . $user->getUID());
				}
				$storage->setBackendOption('user', $value);
			} else {
				throw new InsufficientDataForMeaningfulAnswerException('Custom ldap attribute configured but user ' . $user->getUID() . ' is not an ldap user');
			}
		} else {
			$storage->setBackendOption('user', $credentials['user']);
		}
		$storage->setBackendOption('password', $credentials['password']);
	}

	private function getLdapPropertyForUser(IUser $user, string $property): ?string {
		return $this->ldapFactory->getLDAPProvider()->getUserAttribute($user->getUID(), $property);
	}
}
