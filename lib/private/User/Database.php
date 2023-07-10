<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author adrien <adrien.waksberg@believedigital.com>
 * @author Aldo "xoen" Giambelluca <xoen@xoen.org>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author fabian <fabian@web2.0-apps.de>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Loki3000 <github@labcms.ru>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author nishiki <nishiki@yaegashi.fr>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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

use OCP\AppFramework\Db\TTransactional;
use OCP\Cache\CappedMemoryCache;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\Security\Events\ValidatePasswordPolicyEvent;
use OCP\User\Backend\ABackend;
use OCP\User\Backend\ICheckPasswordBackend;
use OCP\User\Backend\ICountUsersBackend;
use OCP\User\Backend\ICreateUserBackend;
use OCP\User\Backend\IGetDisplayNameBackend;
use OCP\User\Backend\IGetHomeBackend;
use OCP\User\Backend\IGetRealUIDBackend;
use OCP\User\Backend\ISearchKnownUsersBackend;
use OCP\User\Backend\ISetDisplayNameBackend;
use OCP\User\Backend\ISetPasswordBackend;

/**
 * Class for user management in a SQL Database (e.g. MySQL, SQLite)
 */
class Database extends ABackend implements
	ICreateUserBackend,
	ISetPasswordBackend,
	ISetDisplayNameBackend,
	IGetDisplayNameBackend,
	ICheckPasswordBackend,
	IGetHomeBackend,
	ICountUsersBackend,
	ISearchKnownUsersBackend,
	IGetRealUIDBackend {
	/** @var CappedMemoryCache */
	private $cache;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	/** @var IDBConnection */
	private $dbConn;

	/** @var string */
	private $table;

	use TTransactional;

	/**
	 * \OC\User\Database constructor.
	 *
	 * @param IEventDispatcher $eventDispatcher
	 * @param string $table
	 */
	public function __construct($eventDispatcher = null, $table = 'users') {
		$this->cache = new CappedMemoryCache();
		$this->table = $table;
		$this->eventDispatcher = $eventDispatcher ? $eventDispatcher : \OCP\Server::get(IEventDispatcher::class);
	}

	/**
	 * FIXME: This function should not be required!
	 */
	private function fixDI() {
		if ($this->dbConn === null) {
			$this->dbConn = \OC::$server->getDatabaseConnection();
		}
	}

	/**
	 * Create a new user
	 *
	 * @param string $uid The username of the user to create
	 * @param string $password The password of the new user
	 * @return bool
	 *
	 * Creates a new user. Basic checking of username is done in OC_User
	 * itself, not in its subclasses.
	 */
	public function createUser(string $uid, string $password): bool {
		$this->fixDI();

		if (!$this->userExists($uid)) {
			$this->eventDispatcher->dispatchTyped(new ValidatePasswordPolicyEvent($password));

			return $this->atomic(function () use ($uid, $password) {
				$qb = $this->dbConn->getQueryBuilder();
				$qb->insert($this->table)
					->values([
						'uid' => $qb->createNamedParameter($uid),
						'password' => $qb->createNamedParameter(\OC::$server->getHasher()->hash($password)),
						'uid_lower' => $qb->createNamedParameter(mb_strtolower($uid)),
					]);

				$result = $qb->executeStatement();

				// Clear cache
				unset($this->cache[$uid]);
				// Repopulate the cache
				$this->loadUser($uid);

				return (bool) $result;
			}, $this->dbConn);
		}

		return false;
	}

	/**
	 * delete a user
	 *
	 * @param string $uid The username of the user to delete
	 * @return bool
	 *
	 * Deletes a user
	 */
	public function deleteUser($uid) {
		$this->fixDI();

		// Delete user-group-relation
		$query = $this->dbConn->getQueryBuilder();
		$query->delete($this->table)
			->where($query->expr()->eq('uid_lower', $query->createNamedParameter(mb_strtolower($uid))));
		$result = $query->execute();

		if (isset($this->cache[$uid])) {
			unset($this->cache[$uid]);
		}

		return $result ? true : false;
	}

	private function updatePassword(string $uid, string $passwordHash): bool {
		$query = $this->dbConn->getQueryBuilder();
		$query->update($this->table)
			->set('password', $query->createNamedParameter($passwordHash))
			->where($query->expr()->eq('uid_lower', $query->createNamedParameter(mb_strtolower($uid))));
		$result = $query->execute();

		return $result ? true : false;
	}

	/**
	 * Set password
	 *
	 * @param string $uid The username
	 * @param string $password The new password
	 * @return bool
	 *
	 * Change the password of a user
	 */
	public function setPassword(string $uid, string $password): bool {
		$this->fixDI();

		if ($this->userExists($uid)) {
			$this->eventDispatcher->dispatchTyped(new ValidatePasswordPolicyEvent($password));

			$hasher = \OC::$server->getHasher();
			$hashedPassword = $hasher->hash($password);

			$return = $this->updatePassword($uid, $hashedPassword);

			if ($return) {
				$this->cache[$uid]['password'] = $hashedPassword;
			}

			return $return;
		}

		return false;
	}

	/**
	 * Set display name
	 *
	 * @param string $uid The username
	 * @param string $displayName The new display name
	 * @return bool
	 *
	 * @throws \InvalidArgumentException
	 *
	 * Change the display name of a user
	 */
	public function setDisplayName(string $uid, string $displayName): bool {
		if (mb_strlen($displayName) > 64) {
			throw new \InvalidArgumentException('Invalid displayname');
		}

		$this->fixDI();

		if ($this->userExists($uid)) {
			$query = $this->dbConn->getQueryBuilder();
			$query->update($this->table)
				->set('displayname', $query->createNamedParameter($displayName))
				->where($query->expr()->eq('uid_lower', $query->createNamedParameter(mb_strtolower($uid))));
			$query->execute();

			$this->cache[$uid]['displayname'] = $displayName;

			return true;
		}

		return false;
	}

	/**
	 * get display name of the user
	 *
	 * @param string $uid user ID of the user
	 * @return string display name
	 */
	public function getDisplayName($uid): string {
		$uid = (string)$uid;
		$this->loadUser($uid);
		return empty($this->cache[$uid]['displayname']) ? $uid : $this->cache[$uid]['displayname'];
	}

	/**
	 * Get a list of all display names and user ids.
	 *
	 * @param string $search
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array an array of all displayNames (value) and the corresponding uids (key)
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		$limit = $this->fixLimit($limit);

		$this->fixDI();

		$query = $this->dbConn->getQueryBuilder();

		$query->select('uid', 'displayname')
			->from($this->table, 'u')
			->leftJoin('u', 'preferences', 'p', $query->expr()->andX(
				$query->expr()->eq('userid', 'uid'),
				$query->expr()->eq('appid', $query->expr()->literal('settings')),
				$query->expr()->eq('configkey', $query->expr()->literal('email')))
			)
			// sqlite doesn't like re-using a single named parameter here
			->where($query->expr()->iLike('uid', $query->createPositionalParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%')))
			->orWhere($query->expr()->iLike('displayname', $query->createPositionalParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%')))
			->orWhere($query->expr()->iLike('configvalue', $query->createPositionalParameter('%' . $this->dbConn->escapeLikeParameter($search) . '%')))
			->orderBy($query->func()->lower('displayname'), 'ASC')
			->addOrderBy('uid_lower', 'ASC')
			->setMaxResults($limit)
			->setFirstResult($offset);

		$result = $query->executeQuery();
		$displayNames = [];
		while ($row = $result->fetch()) {
			$displayNames[(string)$row['uid']] = (string)$row['displayname'];
		}

		return $displayNames;
	}

	/**
	 * @param string $searcher
	 * @param string $pattern
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 * @since 21.0.1
	 */
	public function searchKnownUsersByDisplayName(string $searcher, string $pattern, ?int $limit = null, ?int $offset = null): array {
		$limit = $this->fixLimit($limit);

		$this->fixDI();

		$query = $this->dbConn->getQueryBuilder();

		$query->select('u.uid', 'u.displayname')
			->from($this->table, 'u')
			->leftJoin('u', 'known_users', 'k', $query->expr()->andX(
				$query->expr()->eq('k.known_user', 'u.uid'),
				$query->expr()->eq('k.known_to', $query->createNamedParameter($searcher))
			))
			->where($query->expr()->eq('k.known_to', $query->createNamedParameter($searcher)))
			->andWhere($query->expr()->orX(
				$query->expr()->iLike('u.uid', $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($pattern) . '%')),
				$query->expr()->iLike('u.displayname', $query->createNamedParameter('%' . $this->dbConn->escapeLikeParameter($pattern) . '%'))
			))
			->orderBy('u.displayname', 'ASC')
			->addOrderBy('u.uid_lower', 'ASC')
			->setMaxResults($limit)
			->setFirstResult($offset);

		$result = $query->execute();
		$displayNames = [];
		while ($row = $result->fetch()) {
			$displayNames[(string)$row['uid']] = (string)$row['displayname'];
		}

		return $displayNames;
	}

	/**
	 * Check if the password is correct
	 *
	 * @param string $loginName The loginname
	 * @param string $password The password
	 * @return string
	 *
	 * Check if the password is correct without logging in the user
	 * returns the user id or false
	 */
	public function checkPassword(string $loginName, string $password) {
		$found = $this->loadUser($loginName);

		if ($found && is_array($this->cache[$loginName])) {
			$storedHash = $this->cache[$loginName]['password'];
			$newHash = '';
			if (\OC::$server->getHasher()->verify($password, $storedHash, $newHash)) {
				if (!empty($newHash)) {
					$this->updatePassword($loginName, $newHash);
				}
				return (string)$this->cache[$loginName]['uid'];
			}
		}

		return false;
	}

	/**
	 * Load an user in the cache
	 *
	 * @param string $uid the username
	 * @return boolean true if user was found, false otherwise
	 */
	private function loadUser($uid) {
		$this->fixDI();

		$uid = (string)$uid;
		if (!isset($this->cache[$uid])) {
			//guests $uid could be NULL or ''
			if ($uid === '') {
				$this->cache[$uid] = false;
				return true;
			}

			$qb = $this->dbConn->getQueryBuilder();
			$qb->select('uid', 'displayname', 'password')
				->from($this->table)
				->where(
					$qb->expr()->eq(
						'uid_lower', $qb->createNamedParameter(mb_strtolower($uid))
					)
				);
			$result = $qb->execute();
			$row = $result->fetch();
			$result->closeCursor();

			// "uid" is primary key, so there can only be a single result
			if ($row !== false) {
				$this->cache[$uid] = [
					'uid' => (string)$row['uid'],
					'displayname' => (string)$row['displayname'],
					'password' => (string)$row['password'],
				];
			} else {
				$this->cache[$uid] = false;
				return false;
			}
		}

		return true;
	}

	/**
	 * Get a list of all users
	 *
	 * @param string $search
	 * @param null|int $limit
	 * @param null|int $offset
	 * @return string[] an array of all uids
	 */
	public function getUsers($search = '', $limit = null, $offset = null) {
		$limit = $this->fixLimit($limit);

		$users = $this->getDisplayNames($search, $limit, $offset);
		$userIds = array_map(function ($uid) {
			return (string)$uid;
		}, array_keys($users));
		sort($userIds, SORT_STRING | SORT_FLAG_CASE);
		return $userIds;
	}

	/**
	 * check if a user exists
	 *
	 * @param string $uid the username
	 * @return boolean
	 */
	public function userExists($uid) {
		$this->loadUser($uid);
		return $this->cache[$uid] !== false;
	}

	/**
	 * get the user's home directory
	 *
	 * @param string $uid the username
	 * @return string|false
	 */
	public function getHome(string $uid) {
		if ($this->userExists($uid)) {
			return \OC::$server->getConfig()->getSystemValueString('datadirectory', \OC::$SERVERROOT . '/data') . '/' . $uid;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function hasUserListings() {
		return true;
	}

	/**
	 * counts the users in the database
	 *
	 * @return int|false
	 */
	public function countUsers() {
		$this->fixDI();

		$query = $this->dbConn->getQueryBuilder();
		$query->select($query->func()->count('uid'))
			->from($this->table);
		$result = $query->executeQuery();

		return $result->fetchOne();
	}

	/**
	 * returns the username for the given login name in the correct casing
	 *
	 * @param string $loginName
	 * @return string|false
	 */
	public function loginName2UserName($loginName) {
		if ($this->userExists($loginName)) {
			return $this->cache[$loginName]['uid'];
		}

		return false;
	}

	/**
	 * Backend name to be shown in user management
	 *
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName() {
		return 'Database';
	}

	public static function preLoginNameUsedAsUserName($param) {
		if (!isset($param['uid'])) {
			throw new \Exception('key uid is expected to be set in $param');
		}

		$backends = \OC::$server->getUserManager()->getBackends();
		foreach ($backends as $backend) {
			if ($backend instanceof Database) {
				/** @var \OC\User\Database $backend */
				$uid = $backend->loginName2UserName($param['uid']);
				if ($uid !== false) {
					$param['uid'] = $uid;
					return;
				}
			}
		}
	}

	public function getRealUID(string $uid): string {
		if (!$this->userExists($uid)) {
			throw new \RuntimeException($uid . ' does not exist');
		}

		return $this->cache[$uid]['uid'];
	}

	private function fixLimit($limit) {
		if (is_int($limit) && $limit >= 0) {
			return $limit;
		}

		return null;
	}
}
