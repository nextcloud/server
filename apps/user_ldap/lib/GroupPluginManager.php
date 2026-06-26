<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP;

use OCP\GroupInterface;
use OCP\Server;
use Psr\Log\LoggerInterface;

class GroupPluginManager {
	private int $respondToActions = 0;

	/** @var array<int, ?ILDAPGroupPlugin> */
	private array $which = [
		GroupInterface::CREATE_GROUP => null,
		GroupInterface::DELETE_GROUP => null,
		GroupInterface::ADD_TO_GROUP => null,
		GroupInterface::REMOVE_FROM_GROUP => null,
		GroupInterface::COUNT_USERS => null,
		GroupInterface::GROUP_DETAILS => null
	];

	private bool $suppressDeletion = false;

	/**
	 * @return int All implemented actions
	 */
	public function getImplementedActions() {
		return $this->respondToActions;
	}

	/**
	 * Registers a group plugin that may implement some actions, overriding User_LDAP's group actions.
	 * @param ILDAPGroupPlugin $plugin
	 */
	public function register(ILDAPGroupPlugin $plugin) {
		$respondToActions = $plugin->respondToActions();
		$this->respondToActions |= $respondToActions;

		foreach ($this->which as $action => $v) {
			if ((bool)($respondToActions & $action)) {
				$this->which[$action] = $plugin;
				Server::get(LoggerInterface::class)->debug('Registered action ' . $action . ' to plugin ' . get_class($plugin), ['app' => 'user_ldap']);
			}
		}
	}

	/**
	 * Signal if there is a registered plugin that implements some given actions
	 * @param int $actions Actions defined in \OCP\GroupInterface, like GroupInterface::REMOVE_FROM_GROUP
	 * @return bool
	 */
	public function implementsActions($actions) {
		return ($actions & $this->respondToActions) == $actions;
	}

	/**
	 * Create a group
	 * @param string $gid Group Id
	 * @return string | null The group DN if group creation was successful.
	 * @throws \Exception
	 */
	public function createGroup($gid) {
		$plugin = $this->which[GroupInterface::CREATE_GROUP];

		if ($plugin) {
			return $plugin->createGroup($gid);
		}
		throw new \Exception('No plugin implements createGroup in this LDAP Backend.');
	}

	public function canDeleteGroup(): bool {
		return !$this->suppressDeletion && $this->implementsActions(GroupInterface::DELETE_GROUP);
	}

	/**
	 * @return bool – the value before the change
	 */
	public function setSuppressDeletion(bool $value): bool {
		$old = $this->suppressDeletion;
		$this->suppressDeletion = $value;
		return $old;
	}

	/**
	 * Delete a group
	 *
	 * @throws \Exception
	 */
	public function deleteGroup(string $gid): bool {
		$plugin = $this->which[GroupInterface::DELETE_GROUP];

		if ($plugin) {
			if ($this->suppressDeletion) {
				return false;
			}
			return $plugin->deleteGroup($gid);
		}
		throw new \Exception('No plugin implements deleteGroup in this LDAP Backend.');
	}

	/**
	 * Add a user to a group
	 * @param string $uid ID of the user to add to group
	 * @param string $gid ID of the group in which add the user
	 * @return bool
	 * @throws \Exception
	 *
	 * Adds a user to a group.
	 */
	public function addToGroup($uid, $gid) {
		$plugin = $this->which[GroupInterface::ADD_TO_GROUP];

		if ($plugin) {
			return $plugin->addToGroup($uid, $gid);
		}
		throw new \Exception('No plugin implements addToGroup in this LDAP Backend.');
	}

	/**
	 * Removes a user from a group
	 * @param string $uid ID of the user to remove from group
	 * @param string $gid ID of the group from which remove the user
	 * @return bool
	 * @throws \Exception
	 *
	 * removes the user from a group.
	 */
	public function removeFromGroup($uid, $gid) {
		$plugin = $this->which[GroupInterface::REMOVE_FROM_GROUP];

		if ($plugin) {
			return $plugin->removeFromGroup($uid, $gid);
		}
		throw new \Exception('No plugin implements removeFromGroup in this LDAP Backend.');
	}

	/**
	 * get the number of all users matching the search string in a group
	 * @param string $gid ID of the group
	 * @param string $search query string
	 * @return int|false
	 * @throws \Exception
	 */
	public function countUsersInGroup($gid, $search = '') {
		$plugin = $this->which[GroupInterface::COUNT_USERS];

		if ($plugin) {
			return $plugin->countUsersInGroup($gid, $search);
		}
		throw new \Exception('No plugin implements countUsersInGroup in this LDAP Backend.');
	}

	/**
	 * get an array with group details
	 * @param string $gid
	 * @return array|false
	 * @throws \Exception
	 */
	public function getGroupDetails($gid) {
		$plugin = $this->which[GroupInterface::GROUP_DETAILS];

		if ($plugin) {
			return $plugin->getGroupDetails($gid);
		}
		throw new \Exception('No plugin implements getGroupDetails in this LDAP Backend.');
	}
}
