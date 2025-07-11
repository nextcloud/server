<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Controller;

use OC\Settings\AuthorizedGroup;
use OCA\Settings\Service\AuthorizedGroupService;
use OCA\Settings\Service\NotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\DB\Exception;
use OCP\IRequest;

class AuthorizedGroupController extends Controller {
	public function __construct(
		string $AppName,
		IRequest $request,
		private AuthorizedGroupService $authorizedGroupService,
	) {
		parent::__construct($AppName, $request);
	}

	/**
	 * @throws NotFoundException
	 * @throws Exception
	 */
	public function saveSettings(array $newGroups, string $class): DataResponse {
		$currentGroups = $this->authorizedGroupService->findExistingGroupsForClass($class);

		foreach ($currentGroups as $group) {
			/** @var AuthorizedGroup $group */
			$removed = true;
			foreach ($newGroups as $groupData) {
				if ($groupData['gid'] === $group->getGroupId()) {
					$removed = false;
					break;
				}
			}
			if ($removed) {
				$this->authorizedGroupService->delete($group->getId());
			}
		}

		foreach ($newGroups as $groupData) {
			$added = true;
			foreach ($currentGroups as $group) {
				/** @var AuthorizedGroup $group */
				if ($groupData['gid'] === $group->getGroupId()) {
					$added = false;
					break;
				}
			}
			if ($added) {
				$this->authorizedGroupService->create($groupData['gid'], $class);
			}
		}

		return new DataResponse(['valid' => true]);
	}
}
