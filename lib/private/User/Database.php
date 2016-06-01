<?php
/**
 * @author adrien <adrien.waksberg@believedigital.com>
 * @author Aldo "xoen" Giambelluca <xoen@xoen.org>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author fabian <fabian@web2.0-apps.de>
 * @author Georg Ehrke <georg@owncloud.com>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author nishiki <nishiki@yaegashi.fr>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
/*
 *
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE `users` (
 *   `uid` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
 *   `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
 *   PRIMARY KEY (`uid`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
 *
 */

namespace OC\User;

use OC\Cache\CappedMemoryCache;

/**
 * Class for user management in a SQL Database (e.g. MySQL, SQLite)
 */
class Database extends \OC\User\Backend implements \OCP\IUserBackend {
	/** @var CappedMemoryCache */
	private $cache;

	/**
	 * OC_User_Database constructor.
	 */
	public function __construct() {
		$this->cache = new CappedMemoryCache();
	}

	/**
	 * Create a new user
	 * @param string $uid The username of the user to create
	 * @param string $password The password of the new user
	 * @return bool
	 *
	 * Creates a new user. Basic checking of username is done in OC_User
	 * itself, not in its subclasses.
	 */
	public function createUser($uid, $password) {
		if (!$this->userExists($uid)) {
			$query = \OC_DB::prepare('INSERT INTO `*PREFIX*users` ( `uid`, `password` ) VALUES( ?, ? )');
			$result = $query->execute(array($uid, \OC::$server->getHasher()->hash($password)));

			return $result ? true : false;
		}

		return false;
	}

	/**
	 * delete a user
	 * @param string $uid The username of the user to delete
	 * @return bool
	 *
	 * Deletes a user
	 */
	public function deleteUser($uid) {
		// Delete user-group-relation
		$query = \OC_DB::prepare('DELETE FROM `*PREFIX*users` WHERE `uid` = ?');
		$result = $query->execute(array($uid));

		if (isset($this->cache[$uid])) {
			unset($this->cache[$uid]);
		}

		return $result ? true : false;
	}

	/**
	 * Set password
	 * @param string $uid The username
	 * @param string $password The new password
	 * @return bool
	 *
	 * Change the password of a user
	 */
	public function setPassword($uid, $password) {
		if ($this->userExists($uid)) {
			$query = \OC_DB::prepare('UPDATE `*PREFIX*users` SET `password` = ? WHERE `uid` = ?');
			$result = $query->execute(array(\OC::$server->getHasher()->hash($password), $uid));

			return $result ? true : false;
		}

		return false;
	}

	/**
	 * Set display name
	 * @param string $uid The username
	 * @param string $displayName The new display name
	 * @return bool
	 *
	 * Change the display name of a user
	 */
	public function setDisplayName($uid, $displayName) {
		if ($this->userExists($uid)) {
			$query = \OC_DB::prepare('UPDATE `*PREFIX*users` SET `displayname` = ? WHERE LOWER(`uid`) = LOWER(?)');
			$query->execute(array($displayName, $uid));
			$this->cache[$uid]['displayname'] = $displayName;

			return true;
		}

		return false;
	}

	/**
	 * get display name of the user
	 * @param string $uid user ID of the user
	 * @return string display name
	 */
	public function getDisplayName($uid) {
		$this->loadUser($uid);
		return empty($this->cache[$uid]['displayname']) ? $uid : $this->cache[$uid]['displayname'];
	}

	/**
	 * Get a list of all display names and user ids.
	 *
	 * @param string $search
	 * @param string|null $limit
	 * @param string|null $offset
	 * @return array an array of all displayNames (value) and the corresponding uids (key)
	 */
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		$parameters = [];
		$searchLike = '';
		if ($search !== '') {
			$parameters[] = '%' . $search . '%';
			$parameters[] = '%' . $search . '%';
			$searchLike = ' WHERE LOWER(`displayname`) LIKE LOWER(?) OR '
				. 'LOWER(`uid`) LIKE LOWER(?)';
		}

		$displayNames = array();
		$query = \OC_DB::prepare('SELECT `uid`, `displayname` FROM `*PREFIX*users`'
			. $searchLike .' ORDER BY `uid` ASC', $limit, $offset);
		$result = $query->execute($parameters);
		while ($row = $result->fetchRow()) {
			$displayNames[$row['uid']] = $row['displayname'];
		}

		return $displayNames;
	}

	/**
	 * Check if the password is correct
	 * @param string $uid The username
	 * @param string $password The password
	 * @return string
	 *
	 * Check if the password is correct without logging in the user
	 * returns the user id or false
	 */
	public function checkPassword($uid, $password) {
		$query = \OC_DB::prepare('SELECT `uid`, `password` FROM `*PREFIX*users` WHERE LOWER(`uid`) = LOWER(?)');
		$result = $query->execute(array($uid));

		$row = $result->fetchRow();
		if ($row) {
			$storedHash = $row['password'];
			$newHash = '';
			if(\OC::$server->getHasher()->verify($password, $storedHash, $newHash)) {
				if(!empty($newHash)) {
					$this->setPassword($uid, $password);
				}
				return $row['uid'];
			}

		}

		return false;
	}

	/**
	 * Load an user in the cache
	 * @param string $uid the username
	 * @return boolean
	 */
	private function loadUser($uid) {
		if (empty($this->cache[$uid])) {
			$query = \OC_DB::prepare('SELECT `uid`, `displayname` FROM `*PREFIX*users` WHERE LOWER(`uid`) = LOWER(?)');
			$result = $query->execute(array($uid));

			if ($result === false) {
				\OCP\Util::writeLog('core', \OC_DB::getErrorMessage(), \OCP\Util::ERROR);
				return false;
			}

			while ($row = $result->fetchRow()) {
				$this->cache[$uid]['uid'] = $row['uid'];
				$this->cache[$uid]['displayname'] = $row['displayname'];
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
		$parameters = [];
		$searchLike = '';
		if ($search !== '') {
			$parameters[] = '%' . $search . '%';
			$searchLike = ' WHERE LOWER(`uid`) LIKE LOWER(?)';
		}

		$query = \OC_DB::prepare('SELECT `uid` FROM `*PREFIX*users`' . $searchLike . ' ORDER BY `uid` ASC', $limit, $offset);
		$result = $query->execute($parameters);
		$users = array();
		while ($row = $result->fetchRow()) {
			$users[] = $row['uid'];
		}
		return $users;
	}

	/**
	 * check if a user exists
	 * @param string $uid the username
	 * @return boolean
	 */
	public function userExists($uid) {
		$this->loadUser($uid);
		return !empty($this->cache[$uid]);
	}

	/**
	 * get the user's home directory
	 * @param string $uid the username
	 * @return string|false
	 */
	public function getHome($uid) {
		if ($this->userExists($uid)) {
			return \OC::$server->getConfig()->getSystemValue("datadirectory", \OC::$SERVERROOT . "/data") . '/' . $uid;
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
	 * @return int|bool
	 */
	public function countUsers() {
		$query = \OC_DB::prepare('SELECT COUNT(*) FROM `*PREFIX*users`');
		$result = $query->execute();
		if ($result === false) {
			\OCP\Util::writeLog('core', \OC_DB::getErrorMessage(), \OCP\Util::ERROR);
			return false;
		}
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
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName(){
		return 'Database';
	}

	public static function preLoginNameUsedAsUserName($param) {
		if(!isset($param['uid'])) {
			throw new \Exception('key uid is expected to be set in $param');
		}

		$backends = \OC::$server->getUserManager()->getBackends();
		foreach ($backends as $backend) {
			if ($backend instanceof \OC\User\Database) {
				/** @var \OC\User\Database $backend */
				$uid = $backend->loginName2UserName($param['uid']);
				if ($uid !== false) {
					$param['uid'] = $uid;
					return;
				}
			}
		}

	}
}
