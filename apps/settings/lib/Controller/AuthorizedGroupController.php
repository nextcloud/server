<?php

/**
 * @copyright Copyright (c) 2021 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Settings\Controller;

use OC\Settings\AuthorizedGroup;
use OCA\Settings\Service\AuthorizedGroupService;
use OCA\Settings\Service\NotFoundException;
use OCP\DB\Exception;
use OCP\IRequest;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class AuthorizedGroupController extends Controller {
	/** @var AuthorizedGroupService $authorizedGroupService */
	private $authorizedGroupService;

	public function __construct(string $AppName, IRequest $request, AuthorizedGroupService $authorizedGroupService) {
		parent::__construct($AppName, $request);
		$this->authorizedGroupService = $authorizedGroupService;
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
