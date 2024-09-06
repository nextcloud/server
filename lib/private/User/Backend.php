<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\User;

use OCP\UserInterface;

/**
 * Abstract base class for user management. Provides methods for querying backend
 * capabilities.
 */
abstract class Backend implements UserInterface {
	/**
	 * error code for functions not provided by the user backend
	 */
	public const NOT_IMPLEMENTED = -501;

	/**
	 * actions that user backends can define
	 */
	public const CREATE_USER = 1;			// 1 << 0
	public const SET_PASSWORD = 16;			// 1 << 4
	public const CHECK_PASSWORD = 256;			// 1 << 8
	public const GET_HOME = 4096;			// 1 << 12
	public const GET_DISPLAYNAME = 65536;		// 1 << 16
	public const SET_DISPLAYNAME = 1048576;		// 1 << 20
	public const PROVIDE_AVATAR = 16777216;		// 1 << 24
	public const COUNT_USERS = 268435456;	// 1 << 28

	protected $possibleActions = [
		self::CREATE_USER => 'createUser',
		self::SET_PASSWORD => 'setPassword',
		self::CHECK_PASSWORD => 'checkPassword',
		self::GET_HOME => 'getHome',
		self::GET_DISPLAYNAME => 'getDisplayName',
		self::SET_DISPLAYNAME => 'setDisplayName',
		self::PROVIDE_AVATAR => 'canChangeAvatar',
		self::COUNT_USERS => 'countUsers',
	];

	/**
	 * Get all supported actions
	 * @return int bitwise-or'ed actions
	 *
	 * Returns the supported actions as int to be
	 * compared with self::CREATE_USER etc.
	 */
	public function getSupportedActions() {
		$actions = 0;
		foreach ($this->possibleActions as $action => $methodName) {
			if (method_exists($this, $methodName)) {
				$actions |= $action;
			}
		}

		return $actions;
	}

	public function implementsActions($actions) {
		return (bool)($this->getSupportedActions() & $actions);
	}

	public function deleteUser($uid) {
		return false;
	}

	public function getUsers($search = '', $limit = null, $offset = null) {
		return [];
	}

	public function userExists($uid) {
		return false;
	}

	/**
	 * get the user's home directory
	 * @param string $uid the username
	 * @return boolean
	 */
	public function getHome($uid) {
		return false;
	}

	public function getDisplayName($uid) {
		return $uid;
	}

	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		$displayNames = [];
		$users = $this->getUsers($search, $limit, $offset);
		foreach ($users as $user) {
			$displayNames[$user] = $user;
		}
		return $displayNames;
	}

	public function hasUserListings() {
		return false;
	}
}
