<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Collaboration\Collaborators;

use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\Collaboration\Collaborators\SearchResultType;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Share\IShare;

class GroupPlugin implements ISearchPlugin {
	protected bool $shareeEnumeration;

	protected bool $shareWithGroupOnly;

	protected bool $shareeEnumerationInGroupOnly;

	protected bool $groupSharingDisabled;

	public function __construct(
		private IConfig $config,
		private IGroupManager $groupManager,
		private IUserSession $userSession,
		private mixed $shareWithGroupOnlyExcludeGroupsList = [],
	) {
		$this->shareeEnumeration = $this->config->getAppValue('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes') === 'yes';
		$this->shareWithGroupOnly = $this->config->getAppValue('core', 'shareapi_only_share_with_group_members', 'no') === 'yes';
		$this->shareeEnumerationInGroupOnly = $this->shareeEnumeration && $this->config->getAppValue('core', 'shareapi_restrict_user_enumeration_to_group', 'no') === 'yes';
		$this->groupSharingDisabled = $this->config->getAppValue('core', 'shareapi_allow_group_sharing', 'yes') === 'no';

		if ($this->shareWithGroupOnly) {
			$this->shareWithGroupOnlyExcludeGroupsList = json_decode($this->config->getAppValue('core', 'shareapi_only_share_with_group_members_exclude_group_list', ''), true) ?? [];
		}
	}

	public function search($search, $limit, $offset, ISearchResult $searchResult): bool {
		if ($this->groupSharingDisabled) {
			return false;
		}

		$hasMoreResults = false;
		$result = ['wide' => [], 'exact' => []];

		$groups = $this->groupManager->search($search, $limit, $offset);
		$groupIds = array_map(function (IGroup $group) {
			return $group->getGID();
		}, $groups);

		if (!$this->shareeEnumeration || count($groups) < $limit) {
			$hasMoreResults = true;
		}

		$userGroups = [];
		if (!empty($groups) && ($this->shareWithGroupOnly || $this->shareeEnumerationInGroupOnly)) {
			// Intersect all the groups that match with the groups this user is a member of
			$userGroups = $this->groupManager->getUserGroups($this->userSession->getUser());
			$userGroups = array_map(function (IGroup $group) {
				return $group->getGID();
			}, $userGroups);
			$groupIds = array_intersect($groupIds, $userGroups);

			// ShareWithGroupOnly filtering
			$groupIds = array_diff($groupIds, $this->shareWithGroupOnlyExcludeGroupsList);
		}

		$lowerSearch = strtolower($search);
		foreach ($groups as $group) {
			if ($group->hideFromCollaboration()) {
				continue;
			}

			// FIXME: use a more efficient approach
			$gid = $group->getGID();
			if (!in_array($gid, $groupIds)) {
				continue;
			}
			if (strtolower($gid) === $lowerSearch || strtolower($group->getDisplayName()) === $lowerSearch) {
				$result['exact'][] = [
					'label' => $group->getDisplayName(),
					'value' => [
						'shareType' => IShare::TYPE_GROUP,
						'shareWith' => $gid,
					],
				];
			} else {
				if ($this->shareeEnumerationInGroupOnly && !in_array($group->getGID(), $userGroups, true)) {
					continue;
				}
				$result['wide'][] = [
					'label' => $group->getDisplayName(),
					'value' => [
						'shareType' => IShare::TYPE_GROUP,
						'shareWith' => $gid,
					],
				];
			}
		}

		if ($offset === 0 && empty($result['exact'])) {
			// On page one we try if the search result has a direct hit on the
			// user id and if so, we add that to the exact match list
			$group = $this->groupManager->get($search);
			if ($group instanceof IGroup && !$group->hideFromCollaboration() && (!$this->shareWithGroupOnly || in_array($group->getGID(), $userGroups))) {
				$result['exact'][] = [
					'label' => $group->getDisplayName(),
					'value' => [
						'shareType' => IShare::TYPE_GROUP,
						'shareWith' => $group->getGID(),
					],
				];
			}
		}

		if (!$this->shareeEnumeration) {
			$result['wide'] = [];
		}

		$type = new SearchResultType('groups');
		$searchResult->addResultSet($type, $result['wide'], $result['exact']);

		return $hasMoreResults;
	}
}
