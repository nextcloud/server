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

namespace OCA\Settings\Listener;

use OCA\Settings\Service\AuthorizedGroupService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Group\Events\GroupDeletedEvent;

class GroupRemovedListener implements IEventListener {

	/** @var AuthorizedGroupService $authorizedGroupService */
	private $authorizedGroupService;

	public function __construct(AuthorizedGroupService $authorizedGroupService) {
		$this->authorizedGroupService = $authorizedGroupService;
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if (!($event instanceof GroupDeletedEvent)) {
			return;
		}

		/** @var GroupDeletedEvent $event */
		$this->authorizedGroupService->removeAuthorizationAssociatedTo($event->getGroup());
	}
}
