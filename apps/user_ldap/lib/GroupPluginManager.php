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
	];

	private bool $suppressDeletion = false;

	public function getImplementedActions(): int {
		return $this->respondToActions;
	}

	/**
	 * Registers a group plugin that may implement some actions, overriding User_LDAP's group actions.
	 */
	public function register(ILDAPGroupPlugin $plugin): void {
		$respondToActions = $plugin->respondToActions();
		$this->respondToActions |= $respondToActions;

		foreach ($this->which as $action => $v) {
			if ($respondToActions & $action) {
				$this->which[$action] = $plugin;
				Server::get(LoggerInterface::class)->debug('Registered action ' . $action . ' to plugin ' . get_class($plugin), ['app' => 'user_ldap']);
			}
		}
	}

	public function implementsActions(int $actions): bool {
		return ($actions & $this->respondToActions) == $actions;
	}

	/**
	 * Create a group
	 * @param string $gid Group Id
	 * @return string | null The group DN if group creation was successful.
	 * @throws \Exception
	 */
	public function createGroup(string $name): ?string {
		$plugin = $this->which[GroupInterface::CREATE_GROUP];

		if ($plugin) {
			return $plugin->createGroup($name);
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
	public function addToGroup(string $uid, string $gid): bool {
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
	public function removeFromGroup(string $uid, string $gid): bool {
		$plugin = $this->which[GroupInterface::REMOVE_FROM_GROUP];

		if ($plugin) {
			return $plugin->removeFromGroup($uid, $gid);
		}
		throw new \Exception('No plugin implements removeFromGroup in this LDAP Backend.');
	}
}
