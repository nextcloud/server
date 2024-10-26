<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Group\Backend;

use OCP\GroupInterface;

/**
 * @since 14.0.0
 */
abstract class ABackend implements GroupInterface, IBatchMethodsBackend {
	/**
	 * @deprecated 14.0.0
	 * @since 14.0.0
	 *
	 * @param int $actions The action to check for
	 * @return bool
	 */
	public function implementsActions($actions): bool {
		$implements = 0;

		if ($this instanceof IAddToGroupBackend) {
			$implements |= GroupInterface::ADD_TO_GROUP;
		}
		if ($this instanceof ICountUsersBackend) {
			$implements |= GroupInterface::COUNT_USERS;
		}
		if ($this instanceof ICreateGroupBackend || $this instanceof ICreateNamedGroupBackend) {
			$implements |= GroupInterface::CREATE_GROUP;
		}
		if ($this instanceof IDeleteGroupBackend) {
			$implements |= GroupInterface::DELETE_GROUP;
		}
		if ($this instanceof IGroupDetailsBackend) {
			$implements |= GroupInterface::GROUP_DETAILS;
		}
		if ($this instanceof IIsAdminBackend) {
			$implements |= GroupInterface::IS_ADMIN;
		}
		if ($this instanceof IRemoveFromGroupBackend) {
			$implements |= GroupInterface::REMOVE_FROM_GOUP;
		}

		return (bool)($actions & $implements);
	}

	/**
	 * @since 28.0.0
	 */
	public function groupsExists(array $gids): array {
		return array_values(array_filter(
			$gids,
			fn (string $gid): bool => $this->groupExists($gid),
		));
	}

	/**
	 * @since 28.0.0
	 */
	public function getGroupsDetails(array $gids): array {
		if (!($this instanceof IGroupDetailsBackend || $this->implementsActions(GroupInterface::GROUP_DETAILS))) {
			throw new \Exception('Should not have been called');
		}
		/** @var IGroupDetailsBackend $this */
		$groupData = [];
		foreach ($gids as $gid) {
			$groupData[$gid] = $this->getGroupDetails($gid);
		}
		return $groupData;
	}
}
