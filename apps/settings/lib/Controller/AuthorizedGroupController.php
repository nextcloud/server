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
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;

class AuthorizedGroupController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private AuthorizedGroupService $authorizedGroupService,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @throws NotFoundException
	 * @throws Exception
	 */
	#[PasswordConfirmationRequired(strict: true)]
	public function saveSettings(array $newGroups, string $class): DataResponse {
		$currentGroups = $this->authorizedGroupService->findExistingGroupsForClass($class);
		$addedGroups = [];
		$removedGroups = [];

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
				$removedGroups[] = $group->getGroupId();
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
				$addedGroups[] = $groupData['gid'];
			}
		}

		if ($addedGroups !== [] || $removedGroups !== []) {
			$this->eventDispatcher->dispatchTyped(new CriticalActionPerformedEvent(
				'Admin delegation for setting "%s" was changed: added groups "%s", removed groups "%s"',
				[
					'settingClass' => $class,
					'addedGroups' => implode(', ', $addedGroups),
					'removedGroups' => implode(', ', $removedGroups),
				],
				false,
				'settings.delegation.updated',
			));
		}

		return new DataResponse(['valid' => true]);
	}
}
