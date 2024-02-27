<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\SystemTag;

use OCP\Constants;
use OCP\EventDispatcher\IEventDispatcher;
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
	) {
		$children = [
			new SystemTagsObjectTypeCollection(
				'files',
				$tagManager,
				$tagMapper,
				$userSession,
				$groupManager,
				function ($name): bool {
					$nodes = \OC::$server->getUserFolder()->getById((int)$name);
					return !empty($nodes);
				},
				function ($name): bool {
					$nodes = \OC::$server->getUserFolder()->getById((int)$name);
					foreach ($nodes as $node) {
						if (($node->getPermissions() & Constants::PERMISSION_UPDATE) === Constants::PERMISSION_UPDATE) {
							return true;
						}
					}
					return false;
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
