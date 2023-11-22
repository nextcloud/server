<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Chan <plus.vincchan@gmail.com>
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
namespace OC\User;

use OC\Hooks\PublicEmitter;
use OC\Memcache\WithLocalCache;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IUser;
use OCP\IUserBackend;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Support\Subscription\IAssertion;
use OCP\User\Backend\IGetRealUIDBackend;
use OCP\User\Backend\ISearchKnownUsersBackend;
use OCP\User\Backend\ICheckPasswordBackend;
use OCP\User\Backend\ICountUsersBackend;
use OCP\User\Backend\IProvideEnabledStateBackend;
use OCP\User\Events\BeforeUserCreatedEvent;
use OCP\User\Events\UserCreatedEvent;
use OCP\UserInterface;

/**
 * Class Manager
 *
 * Hooks available in scope \OC\User:
 * - preSetPassword(\OC\User\User $user, string $password, string $recoverPassword)
 * - postSetPassword(\OC\User\User $user, string $password, string $recoverPassword)
 * - preDelete(\OC\User\User $user)
 * - postDelete(\OC\User\User $user)
 * - preCreateUser(string $uid, string $password)
 * - postCreateUser(\OC\User\User $user, string $password)
 * - change(\OC\User\User $user)
 * - assignedUserId(string $uid)
 * - preUnassignedUserId(string $uid)
 * - postUnassignedUserId(string $uid)
 *
 * @package OC\User
 */
class Manager extends PublicEmitter implements IUserManager {
	/**
	 * @var \OCP\UserInterface[] $backends
	 */
	private $backends = [];

	/**
	 * @var \OC\User\User[] $cachedUsers
	 */
	private $cachedUsers = [];

	/** @var IConfig */
	private $config;

	/** @var ICache */
	private $cache;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	private DisplayNameCache $displayNameCache;

	public function __construct(IConfig $config,
								ICacheFactory $cacheFactory,
								IEventDispatcher $eventDispatcher) {
		$this->config = $config;
		$this->cache = new WithLocalCache($cacheFactory->createDistributed('user_backend_map'));
		$cachedUsers = &$this->cachedUsers;
		$this->listen('\OC\User', 'postDelete', function ($user) use (&$cachedUsers) {
			/** @var \OC\User\User $user */
			unset($cachedUsers[$user->getUID()]);
		});
		$this->eventDispatcher = $eventDispatcher;
		$this->displayNameCache = new DisplayNameCache($cacheFactory, $this);
	}

	/**
	 * Get the active backends
	 * @return \OCP\UserInterface[]
	 */
	public function getBackends() {
		return $this->backends;
	}

	/**
	 * register a user backend
	 *
	 * @param \OCP\UserInterface $backend
	 */
	public function registerBackend($backend) {
		$this->backends[] = $backend;
	}

	/**
	 * remove a user backend
	 *
	 * @param \OCP\UserInterface $backend
	 */
	public function removeBackend($backend) {
		$this->cachedUsers = [];
		if (($i = array_search($backend, $this->backends)) !== false) {
			unset($this->backends[$i]);
		}
	}

	/**
	 * remove all user backends
	 */
	public function clearBackends() {
		$this->cachedUsers = [];
		$this->backends = [];
	}

	/**
	 * get a user by user id
	 *
	 * @param string $uid
	 * @return \OC\User\User|null Either the user or null if the specified user does not exist
	 */
	public function get($uid) {
		if (is_null($uid) || $uid === '' || $uid === false) {
			return null;
		}
		if (isset($this->cachedUsers[$uid])) { //check the cache first to prevent having to loop over the backends
			return $this->cachedUsers[$uid];
		}

		$cachedBackend = $this->cache->get(sha1($uid));
		if ($cachedBackend !== null && isset($this->backends[$cachedBackend])) {
			// Cache has the info of the user backend already, so ask that one directly
			$backend = $this->backends[$cachedBackend];
			if ($backend->userExists($uid)) {
				return $this->getUserObject($uid, $backend);
			}
		}

		foreach ($this->backends as $i => $backend) {
			if ($i === $cachedBackend) {
				// Tried that one already
				continue;
			}

			if ($backend->userExists($uid)) {
				// Hash $uid to ensure that only valid characters are used for the cache key
				$this->cache->set(sha1($uid), $i, 300);
				return $this->getUserObject($uid, $backend);
			}
		}
		return null;
	}

	public function getDisplayName(string $uid): ?string {
		return $this->displayNameCache->getDisplayName($uid);
	}

	/**
	 * get or construct the user object
	 *
	 * @param string $uid
	 * @param \OCP\UserInterface $backend
	 * @param bool $cacheUser If false the newly created user object will not be cached
	 * @return \OC\User\User
	 */
	public function getUserObject($uid, $backend, $cacheUser = true) {
		if ($backend instanceof IGetRealUIDBackend) {
			$uid = $backend->getRealUID($uid);
		}

		if (isset($this->cachedUsers[$uid])) {
			return $this->cachedUsers[$uid];
		}

		$user = new User($uid, $backend, $this->eventDispatcher, $this, $this->config);
		if ($cacheUser) {
			$this->cachedUsers[$uid] = $user;
		}
		return $user;
	}

	/**
	 * check if a user exists
	 *
	 * @param string $uid
	 * @return bool
	 */
	public function userExists($uid) {
		$user = $this->get($uid);
		return ($user !== null);
	}

	/**
	 * Check if the password is valid for the user
	 *
	 * @param string $loginName
	 * @param string $password
	 * @return IUser|false the User object on success, false otherwise
	 */
	public function checkPassword($loginName, $password) {
		$result = $this->checkPasswordNoLogging($loginName, $password);

		if ($result === false) {
			\OC::$server->getLogger()->warning('Login failed: \''. $loginName .'\' (Remote IP: \''. \OC::$server->getRequest()->getRemoteAddress(). '\')', ['app' => 'core']);
		}

		return $result;
	}

	/**
	 * Check if the password is valid for the user
	 *
	 * @internal
	 * @param string $loginName
	 * @param string $password
	 * @return IUser|false the User object on success, false otherwise
	 */
	public function checkPasswordNoLogging($loginName, $password) {
		$loginName = str_replace("\0", '', $loginName);
		$password = str_replace("\0", '', $password);

		$cachedBackend = $this->cache->get($loginName);
		if ($cachedBackend !== null && isset($this->backends[$cachedBackend])) {
			$backends = [$this->backends[$cachedBackend]];
		} else {
			$backends = $this->backends;
		}
		foreach ($backends as $backend) {
			if ($backend instanceof ICheckPasswordBackend || $backend->implementsActions(Backend::CHECK_PASSWORD)) {
				/** @var ICheckPasswordBackend $backend */
				$uid = $backend->checkPassword($loginName, $password);
				if ($uid !== false) {
					return $this->getUserObject($uid, $backend);
				}
			}
		}

		// since http basic auth doesn't provide a standard way of handling non ascii password we allow password to be urlencoded
		// we only do this decoding after using the plain password fails to maintain compatibility with any password that happens
		// to contain urlencoded patterns by "accident".
		$password = urldecode($password);

		foreach ($backends as $backend) {
			if ($backend instanceof ICheckPasswordBackend || $backend->implementsActions(Backend::CHECK_PASSWORD)) {
				/** @var ICheckPasswordBackend|UserInterface $backend */
				$uid = $backend->checkPassword($loginName, $password);
				if ($uid !== false) {
					return $this->getUserObject($uid, $backend);
				}
			}
		}

		return false;
	}

	/**
	 * Search by user id
	 *
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @return IUser[]
	 * @deprecated since 27.0.0, use searchDisplayName instead
	 */
	public function search($pattern, $limit = null, $offset = null) {
		$users = [];
		foreach ($this->backends as $backend) {
			$backendUsers = $backend->getUsers($pattern, $limit, $offset);
			if (is_array($backendUsers)) {
				foreach ($backendUsers as $uid) {
					$users[$uid] = new LazyUser($uid, $this, null, $backend);
				}
			}
		}

		uasort($users, function (IUser $a, IUser $b) {
			return strcasecmp($a->getUID(), $b->getUID());
		});
		return $users;
	}

	/**
	 * Search by displayName
	 *
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @return IUser[]
	 */
	public function searchDisplayName($pattern, $limit = null, $offset = null) {
		$users = [];
		foreach ($this->backends as $backend) {
			$backendUsers = $backend->getDisplayNames($pattern, $limit, $offset);
			if (is_array($backendUsers)) {
				foreach ($backendUsers as $uid => $displayName) {
					$users[] = new LazyUser($uid, $this, $displayName, $backend);
				}
			}
		}

		usort($users, function (IUser $a, IUser $b) {
			return strcasecmp($a->getDisplayName(), $b->getDisplayName());
		});
		return $users;
	}

	/**
	 * @return IUser[]
	 */
	public function getDisabledUsers(?int $limit = null, int $offset = 0): array {
		$users = $this->config->getUsersForUserValue('core', 'enabled', 'false');
		$users = array_combine(
			$users,
			array_map(
				fn (string $uid): IUser => new LazyUser($uid, $this),
				$users
			)
		);

		$tempLimit = ($limit === null ? null : $limit + $offset);
		foreach ($this->backends as $backend) {
			if (($tempLimit !== null) && (count($users) >= $tempLimit)) {
				break;
			}
			if ($backend instanceof IProvideEnabledStateBackend) {
				$backendUsers = $backend->getDisabledUserList(($tempLimit === null ? null : $tempLimit - count($users)));
				foreach ($backendUsers as $uid) {
					$users[$uid] = new LazyUser($uid, $this, null, $backend);
				}
			}
		}

		return array_slice($users, $offset, $limit);
	}

	/**
	 * Search known users (from phonebook sync) by displayName
	 *
	 * @param string $searcher
	 * @param string $pattern
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return IUser[]
	 */
	public function searchKnownUsersByDisplayName(string $searcher, string $pattern, ?int $limit = null, ?int $offset = null): array {
		$users = [];
		foreach ($this->backends as $backend) {
			if ($backend instanceof ISearchKnownUsersBackend) {
				$backendUsers = $backend->searchKnownUsersByDisplayName($searcher, $pattern, $limit, $offset);
			} else {
				// Better than nothing, but filtering after pagination can remove lots of results.
				$backendUsers = $backend->getDisplayNames($pattern, $limit, $offset);
			}
			if (is_array($backendUsers)) {
				foreach ($backendUsers as $uid => $displayName) {
					$users[] = $this->getUserObject($uid, $backend);
				}
			}
		}

		usort($users, function ($a, $b) {
			/**
			 * @var IUser $a
			 * @var IUser $b
			 */
			return strcasecmp($a->getDisplayName(), $b->getDisplayName());
		});
		return $users;
	}

	/**
	 * @param string $uid
	 * @param string $password
	 * @return false|IUser the created user or false
	 * @throws \InvalidArgumentException
	 * @throws HintException
	 */
	public function createUser($uid, $password) {
		// DI injection is not used here as IRegistry needs the user manager itself for user count and thus it would create a cyclic dependency
		/** @var IAssertion $assertion */
		$assertion = \OC::$server->get(IAssertion::class);
		$assertion->createUserIsLegit();

		$localBackends = [];
		foreach ($this->backends as $backend) {
			if ($backend instanceof Database) {
				// First check if there is another user backend
				$localBackends[] = $backend;
				continue;
			}

			if ($backend->implementsActions(Backend::CREATE_USER)) {
				return $this->createUserFromBackend($uid, $password, $backend);
			}
		}

		foreach ($localBackends as $backend) {
			if ($backend->implementsActions(Backend::CREATE_USER)) {
				return $this->createUserFromBackend($uid, $password, $backend);
			}
		}

		return false;
	}

	/**
	 * @param string $uid
	 * @param string $password
	 * @param UserInterface $backend
	 * @return IUser|false
	 * @throws \InvalidArgumentException
	 */
	public function createUserFromBackend($uid, $password, UserInterface $backend) {
		$l = \OC::$server->getL10N('lib');

		$this->validateUserId($uid, true);

		// No empty password
		if (trim($password) === '') {
			throw new \InvalidArgumentException($l->t('A valid password must be provided'));
		}

		// Check if user already exists
		if ($this->userExists($uid)) {
			throw new \InvalidArgumentException($l->t('The username is already being used'));
		}

		/** @deprecated 21.0.0 use BeforeUserCreatedEvent event with the IEventDispatcher instead */
		$this->emit('\OC\User', 'preCreateUser', [$uid, $password]);
		$this->eventDispatcher->dispatchTyped(new BeforeUserCreatedEvent($uid, $password));
		$state = $backend->createUser($uid, $password);
		if ($state === false) {
			throw new \InvalidArgumentException($l->t('Could not create user'));
		}
		$user = $this->getUserObject($uid, $backend);
		if ($user instanceof IUser) {
			/** @deprecated 21.0.0 use UserCreatedEvent event with the IEventDispatcher instead */
			$this->emit('\OC\User', 'postCreateUser', [$user, $password]);
			$this->eventDispatcher->dispatchTyped(new UserCreatedEvent($user, $password));
			return $user;
		}
		return false;
	}

	/**
	 * returns how many users per backend exist (if supported by backend)
	 *
	 * @param boolean $hasLoggedIn when true only users that have a lastLogin
	 *                entry in the preferences table will be affected
	 * @return array<string, int> an array of backend class as key and count number as value
	 */
	public function countUsers() {
		$userCountStatistics = [];
		foreach ($this->backends as $backend) {
			if ($backend instanceof ICountUsersBackend || $backend->implementsActions(Backend::COUNT_USERS)) {
				/** @var ICountUsersBackend|IUserBackend $backend */
				$backendUsers = $backend->countUsers();
				if ($backendUsers !== false) {
					if ($backend instanceof IUserBackend) {
						$name = $backend->getBackendName();
					} else {
						$name = get_class($backend);
					}
					if (isset($userCountStatistics[$name])) {
						$userCountStatistics[$name] += $backendUsers;
					} else {
						$userCountStatistics[$name] = $backendUsers;
					}
				}
			}
		}
		return $userCountStatistics;
	}

	/**
	 * returns how many users per backend exist in the requested groups (if supported by backend)
	 *
	 * @param IGroup[] $groups an array of gid to search in
	 * @return array|int an array of backend class as key and count number as value
	 *                if $hasLoggedIn is true only an int is returned
	 */
	public function countUsersOfGroups(array $groups) {
		$users = [];
		foreach ($groups as $group) {
			$usersIds = array_map(function ($user) {
				return $user->getUID();
			}, $group->getUsers());
			$users = array_merge($users, $usersIds);
		}
		return count(array_unique($users));
	}

	/**
	 * The callback is executed for each user on each backend.
	 * If the callback returns false no further users will be retrieved.
	 *
	 * @psalm-param \Closure(\OCP\IUser):?bool $callback
	 * @param string $search
	 * @param boolean $onlySeen when true only users that have a lastLogin entry
	 *                in the preferences table will be affected
	 * @since 9.0.0
	 */
	public function callForAllUsers(\Closure $callback, $search = '', $onlySeen = false) {
		if ($onlySeen) {
			$this->callForSeenUsers($callback);
		} else {
			foreach ($this->getBackends() as $backend) {
				$limit = 500;
				$offset = 0;
				do {
					$users = $backend->getUsers($search, $limit, $offset);
					foreach ($users as $uid) {
						if (!$backend->userExists($uid)) {
							continue;
						}
						$user = $this->getUserObject($uid, $backend, false);
						$return = $callback($user);
						if ($return === false) {
							break;
						}
					}
					$offset += $limit;
				} while (count($users) >= $limit);
			}
		}
	}

	/**
	 * returns how many users are disabled
	 *
	 * @return int
	 * @since 12.0.0
	 */
	public function countDisabledUsers(): int {
		$queryBuilder = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$queryBuilder->select($queryBuilder->func()->count('*'))
			->from('preferences')
			->where($queryBuilder->expr()->eq('appid', $queryBuilder->createNamedParameter('core')))
			->andWhere($queryBuilder->expr()->eq('configkey', $queryBuilder->createNamedParameter('enabled')))
			->andWhere($queryBuilder->expr()->eq('configvalue', $queryBuilder->createNamedParameter('false'), IQueryBuilder::PARAM_STR));


		$result = $queryBuilder->execute();
		$count = $result->fetchOne();
		$result->closeCursor();

		if ($count !== false) {
			$count = (int)$count;
		} else {
			$count = 0;
		}

		return $count;
	}

	/**
	 * returns how many users are disabled in the requested groups
	 *
	 * @param array $groups groupids to search
	 * @return int
	 * @since 14.0.0
	 */
	public function countDisabledUsersOfGroups(array $groups): int {
		$queryBuilder = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$queryBuilder->select($queryBuilder->createFunction('COUNT(DISTINCT ' . $queryBuilder->getColumnName('uid') . ')'))
			->from('preferences', 'p')
			->innerJoin('p', 'group_user', 'g', $queryBuilder->expr()->eq('p.userid', 'g.uid'))
			->where($queryBuilder->expr()->eq('appid', $queryBuilder->createNamedParameter('core')))
			->andWhere($queryBuilder->expr()->eq('configkey', $queryBuilder->createNamedParameter('enabled')))
			->andWhere($queryBuilder->expr()->eq('configvalue', $queryBuilder->createNamedParameter('false'), IQueryBuilder::PARAM_STR))
			->andWhere($queryBuilder->expr()->in('gid', $queryBuilder->createNamedParameter($groups, IQueryBuilder::PARAM_STR_ARRAY)));

		$result = $queryBuilder->execute();
		$count = $result->fetchOne();
		$result->closeCursor();

		if ($count !== false) {
			$count = (int)$count;
		} else {
			$count = 0;
		}

		return $count;
	}

	/**
	 * returns how many users have logged in once
	 *
	 * @return int
	 * @since 11.0.0
	 */
	public function countSeenUsers() {
		$queryBuilder = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$queryBuilder->select($queryBuilder->func()->count('*'))
			->from('preferences')
			->where($queryBuilder->expr()->eq('appid', $queryBuilder->createNamedParameter('login')))
			->andWhere($queryBuilder->expr()->eq('configkey', $queryBuilder->createNamedParameter('lastLogin')));

		$query = $queryBuilder->execute();

		$result = (int)$query->fetchOne();
		$query->closeCursor();

		return $result;
	}

	/**
	 * @param \Closure $callback
	 * @psalm-param \Closure(\OCP\IUser):?bool $callback
	 * @since 11.0.0
	 */
	public function callForSeenUsers(\Closure $callback) {
		$limit = 1000;
		$offset = 0;
		do {
			$userIds = $this->getSeenUserIds($limit, $offset);
			$offset += $limit;
			foreach ($userIds as $userId) {
				foreach ($this->backends as $backend) {
					if ($backend->userExists($userId)) {
						$user = $this->getUserObject($userId, $backend, false);
						$return = $callback($user);
						if ($return === false) {
							return;
						}
						break;
					}
				}
			}
		} while (count($userIds) >= $limit);
	}

	/**
	 * Getting all userIds that have a listLogin value requires checking the
	 * value in php because on oracle you cannot use a clob in a where clause,
	 * preventing us from doing a not null or length(value) > 0 check.
	 *
	 * @param int $limit
	 * @param int $offset
	 * @return string[] with user ids
	 */
	private function getSeenUserIds($limit = null, $offset = null) {
		$queryBuilder = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$queryBuilder->select(['userid'])
			->from('preferences')
			->where($queryBuilder->expr()->eq(
				'appid', $queryBuilder->createNamedParameter('login'))
			)
			->andWhere($queryBuilder->expr()->eq(
				'configkey', $queryBuilder->createNamedParameter('lastLogin'))
			)
			->andWhere($queryBuilder->expr()->isNotNull('configvalue')
			);

		if ($limit !== null) {
			$queryBuilder->setMaxResults($limit);
		}
		if ($offset !== null) {
			$queryBuilder->setFirstResult($offset);
		}
		$query = $queryBuilder->execute();
		$result = [];

		while ($row = $query->fetch()) {
			$result[] = $row['userid'];
		}

		$query->closeCursor();

		return $result;
	}

	/**
	 * @param string $email
	 * @return IUser[]
	 * @since 9.1.0
	 */
	public function getByEmail($email) {
		// looking for 'email' only (and not primary_mail) is intentional
		$userIds = $this->config->getUsersForUserValueCaseInsensitive('settings', 'email', $email);

		$users = array_map(function ($uid) {
			return $this->get($uid);
		}, $userIds);

		return array_values(array_filter($users, function ($u) {
			return ($u instanceof IUser);
		}));
	}

	/**
	 * @param string $uid
	 * @param bool $checkDataDirectory
	 * @throws \InvalidArgumentException Message is an already translated string with a reason why the id is not valid
	 * @since 26.0.0
	 */
	public function validateUserId(string $uid, bool $checkDataDirectory = false): void {
		$l = Server::get(IFactory::class)->get('lib');

		// Check the name for bad characters
		// Allowed are: "a-z", "A-Z", "0-9", spaces and "_.@-'"
		if (preg_match('/[^a-zA-Z0-9 _.@\-\']/', $uid)) {
			throw new \InvalidArgumentException($l->t('Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", spaces and "_.@-\'"'));
		}

		// No empty username
		if (trim($uid) === '') {
			throw new \InvalidArgumentException($l->t('A valid username must be provided'));
		}

		// No whitespace at the beginning or at the end
		if (trim($uid) !== $uid) {
			throw new \InvalidArgumentException($l->t('Username contains whitespace at the beginning or at the end'));
		}

		// Username only consists of 1 or 2 dots (directory traversal)
		if ($uid === '.' || $uid === '..') {
			throw new \InvalidArgumentException($l->t('Username must not consist of dots only'));
		}

		if (!$this->verifyUid($uid, $checkDataDirectory)) {
			throw new \InvalidArgumentException($l->t('Username is invalid because files already exist for this user'));
		}
	}

	private function verifyUid(string $uid, bool $checkDataDirectory = false): bool {
		$appdata = 'appdata_' . $this->config->getSystemValueString('instanceid');

		if (\in_array($uid, [
			'.htaccess',
			'files_external',
			'__groupfolders',
			'.ocdata',
			'owncloud.log',
			'nextcloud.log',
			$appdata], true)) {
			return false;
		}

		if (!$checkDataDirectory) {
			return true;
		}

		$dataDirectory = $this->config->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data');

		return !file_exists(rtrim($dataDirectory, '/') . '/' . $uid);
	}

	public function getDisplayNameCache(): DisplayNameCache {
		return $this->displayNameCache;
	}
}
