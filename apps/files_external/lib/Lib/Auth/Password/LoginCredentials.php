<?php
/**
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 *
 * @author blizzz <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_External\Lib\Auth\Password;

use OCA\Files_External\Lib\Auth\AuthMechanism;
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

	/** @var ISession */
	protected $session;

	/** @var ICredentialsManager */
	protected $credentialsManager;

	/** @var CredentialsStore */
	private $credentialsStore;

	/** @var ILDAPProviderFactory */
	private $ldapFactory;

	public function __construct(
		IL10N $l,
		ISession $session,
		ICredentialsManager $credentialsManager,
		CredentialsStore $credentialsStore,
		IEventDispatcher $eventDispatcher,
		ILDAPProviderFactory $ldapFactory
	) {
		$this->session = $session;
		$this->credentialsManager = $credentialsManager;
		$this->credentialsStore = $credentialsStore;
		$this->ldapFactory = $ldapFactory;

		$this
			->setIdentifier('password::logincredentials')
			->setScheme(self::SCHEME_PASSWORD)
			->setText($l->t('Log-in credentials, save in database'))
			->addParameters([
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

	public function manipulateStorageConfig(StorageConfig &$storage, IUser $user = null) {
		if (!isset($user)) {
			throw new InsufficientDataForMeaningfulAnswerException('No login credentials saved');
		}
		$credentials = $this->getCredentials($user);

		$loginKey = $storage->getBackendOption("login_ldap_attr");
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
