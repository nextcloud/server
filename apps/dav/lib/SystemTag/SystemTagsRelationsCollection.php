<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\SystemTag;

use OCP\Constants;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\SystemTagsEntityEvent;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\SimpleCollection;

class SystemTagsRelationsCollection extends SimpleCollection {
	public function __construct(
		ISystemTagManager $tagManager,
		ISystemTagObjectMapper $tagMapper,
		IUserSession $userSession,
		IGroupManager $groupManager,
		IEventDispatcher $dispatcher,
		IRootFolder $rootFolder,
	) {
		$children = [
			// Only files are supported at the moment
			// Also see SystemTagPlugin::OBJECTIDS_PROPERTYNAME supported types
			new SystemTagsObjectTypeCollection(
				'files',
				$tagManager,
				$tagMapper,
				$userSession,
				$groupManager,
				function (string $name) use ($rootFolder, $userSession): bool {
					$user = $userSession->getUser();
					if ($user) {
						$node = $rootFolder->getUserFolder($user->getUID())->getFirstNodeById((int)$name);
						return $node !== null;
					} else {
						return false;
					}
				},
				function (string $name) use ($rootFolder, $userSession): bool {
					$user = $userSession->getUser();
					if ($user) {
						$nodes = $rootFolder->getUserFolder($user->getUID())->getById((int)$name);
						foreach ($nodes as $node) {
							if (($node->getPermissions() & Constants::PERMISSION_UPDATE) === Constants::PERMISSION_UPDATE) {
								return true;
							}
						}
						return false;
					} else {
						return false;
					}
				},
			),
		];

		$event = new SystemTagsEntityEvent();
		$dispatcher->dispatch(SystemTagsEntityEvent::EVENT_ENTITY, $event);
		$dispatcher->dispatchTyped($event);

		foreach ($event->getEntityCollections() as $entity => $entityExistsFunction) {
			$children[] = new SystemTagsObjectTypeCollection(
				$entity,
				$tagManager,
				$tagMapper,
				$userSession,
				$groupManager,
				$entityExistsFunction,
				fn ($name) => true,
			);
		}

		parent::__construct('root', $children);
	}

	public function getName() {
		return 'systemtags-relations';
	}

	public function setName($name) {
		throw new Forbidden('Permission denied to rename this collection');
	}
}
