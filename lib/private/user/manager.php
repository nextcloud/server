<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\User;

use OC\Hooks\PublicEmitter;
use OCP\IUserManager;
use OCP\IConfig;

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
 *
 * @package OC\User
 */
class Manager extends PublicEmitter implements IUserManager {
	/**
	 * @var \OCP\UserInterface [] $backends
	 */
	private $backends = array();

	/**
	 * @var \OC\User\User[] $cachedUsers
	 */
	private $cachedUsers = array();

	/**
	 * @var \OCP\IConfig $config
	 */
	private $config;

	/**
	 * @param \OCP\IConfig $config
	 */
	public function __construct(IConfig $config = null) {
		$this->config = $config;
		$cachedUsers = &$this->cachedUsers;
		$this->listen('\OC\User', 'postDelete', function ($user) use (&$cachedUsers) {
			/** @var \OC\User\User $user */
			unset($cachedUsers[$user->getUID()]);
		});
		$this->listen('\OC\User', 'postLogin', function ($user) {
			/** @var \OC\User\User $user */
			$user->updateLastLoginTimestamp();
		});
		$this->listen('\OC\User', 'postRememberedLogin', function ($user) {
			/** @var \OC\User\User $user */
			$user->updateLastLoginTimestamp();
		});
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
		$this->cachedUsers = array();
		if (($i = array_search($backend, $this->backends)) !== false) {
			unset($this->backends[$i]);
		}
	}

	/**
	 * remove all user backends
	 */
	public function clearBackends() {
		$this->cachedUsers = array();
		$this->backends = array();
	}

	/**
	 * get a user by user id
	 *
	 * @param string $uid
	 * @return \OC\User\User
	 */
	public function get($uid) {
		if (isset($this->cachedUsers[$uid])) { //check the cache first to prevent having to loop over the backends
			return $this->cachedUsers[$uid];
		}
		foreach ($this->backends as $backend) {
			if ($backend->userExists($uid)) {
				return $this->getUserObject($uid, $backend);
			}
		}
		return null;
	}

	/**
	 * get or construct the user object
	 *
	 * @param string $uid
	 * @param \OCP\UserInterface $backend
	 * @return \OC\User\User
	 */
	protected function getUserObject($uid, $backend) {
		if (isset($this->cachedUsers[$uid])) {
			return $this->cachedUsers[$uid];
		}
		$this->cachedUsers[$uid] = new User($uid, $backend, $this, $this->config);
		return $this->cachedUsers[$uid];
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
	 * @param string $loginname
	 * @param string $password
	 * @return mixed the User object on success, false otherwise
	 */
	public function checkPassword($loginname, $password) {
		$loginname = str_replace("\0", '', $loginname);
		$password = str_replace("\0", '', $password);
		
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(\OC_User_Backend::CHECK_PASSWORD)) {
				$uid = $backend->checkPassword($loginname, $password);
				if ($uid !== false) {
					return $this->getUserObject($uid, $backend);
				}
			}
		}

		$remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		$forwardedFor = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';

		\OC::$server->getLogger()->warning('Login failed: \''. $loginname .'\' (Remote IP: \''. $remoteAddr .'\', X-Forwarded-For: \''. $forwardedFor .'\')', array('app' => 'core'));
		return false;
	}

	/**
	 * search by user id
	 *
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @return \OC\User\User[]
	 */
	public function search($pattern, $limit = null, $offset = null) {
		$users = array();
		foreach ($this->backends as $backend) {
			$backendUsers = $backend->getUsers($pattern, $limit, $offset);
			if (is_array($backendUsers)) {
				foreach ($backendUsers as $uid) {
					$users[$uid] = $this->getUserObject($uid, $backend);
				}
			}
		}

		uasort($users, function ($a, $b) {
			/**
			 * @var \OC\User\User $a
			 * @var \OC\User\User $b
			 */
			return strcmp($a->getUID(), $b->getUID());
		});
		return $users;
	}

	/**
	 * search by displayName
	 *
	 * @param string $pattern
	 * @param int $limit
	 * @param int $offset
	 * @return \OC\User\User[]
	 */
	public function searchDisplayName($pattern, $limit = null, $offset = null) {
		$users = array();
		foreach ($this->backends as $backend) {
			$backendUsers = $backend->getDisplayNames($pattern, $limit, $offset);
			if (is_array($backendUsers)) {
				foreach ($backendUsers as $uid => $displayName) {
					$users[] = $this->getUserObject($uid, $backend);
				}
			}
		}

		usort($users, function ($a, $b) {
			/**
			 * @var \OC\User\User $a
			 * @var \OC\User\User $b
			 */
			return strcmp($a->getDisplayName(), $b->getDisplayName());
		});
		return $users;
	}

	/**
	 * @param string $uid
	 * @param string $password
	 * @throws \Exception
	 * @return bool|\OC\User\User the created user or false
	 */
	public function createUser($uid, $password) {
		$l = \OC::$server->getL10N('lib');
		// Check the name for bad characters
		// Allowed are: "a-z", "A-Z", "0-9" and "_.@-"
		if (preg_match('/[^a-zA-Z0-9 _\.@\-]/', $uid)) {
			throw new \Exception($l->t('Only the following characters are allowed in a username:'
				. ' "a-z", "A-Z", "0-9", and "_.@-"'));
		}
		// No empty username
		if (trim($uid) == '') {
			throw new \Exception($l->t('A valid username must be provided'));
		}
		// No empty password
		if (trim($password) == '') {
			throw new \Exception($l->t('A valid password must be provided'));
		}

		// Check if user already exists
		if ($this->userExists($uid)) {
			throw new \Exception($l->t('The username is already being used'));
		}

		$this->emit('\OC\User', 'preCreateUser', array($uid, $password));
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(\OC_User_Backend::CREATE_USER)) {
				$backend->createUser($uid, $password);
				$user = $this->getUserObject($uid, $backend);
				$this->emit('\OC\User', 'postCreateUser', array($user, $password));
				return $user;
			}
		}
		return false;
	}

	/**
	 * returns how many users per backend exist (if supported by backend)
	 *
	 * @return array an array of backend class as key and count number as value
	 */
	public function countUsers() {
		$userCountStatistics = array();
		foreach ($this->backends as $backend) {
			if ($backend->implementsActions(\OC_User_Backend::COUNT_USERS)) {
				$backendusers = $backend->countUsers();
				if($backendusers !== false) {
					if($backend instanceof \OCP\IUserBackend) {
						$name = $backend->getBackendName();
					} else {
						$name = get_class($backend);
					}
					if(isset($userCountStatistics[$name])) {
						$userCountStatistics[$name] += $backendusers;
					} else {
						$userCountStatistics[$name] = $backendusers;
					}
				}
			}
		}
		return $userCountStatistics;
	}
}
