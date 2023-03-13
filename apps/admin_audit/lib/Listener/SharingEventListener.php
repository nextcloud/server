<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\AdminAudit\Listener;

use OCA\AdminAudit\Actions\Action;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Share\Events\ShareCreatedEvent;
use OCP\Share\Events\ShareDeletedEvent;
use OCP\Share\IShare;

/**
 * @template-implements UserManagementEventListener<ShareCreatedEvent|ShareDeletedEvent>
 */
class SharingEventListener extends Action implements IEventListener {
	public function handle(Event $event): void {
		if ($event instanceof ShareCreatedEvent) {
			$this->shareCreated($event);
		} elseif ($event instanceof ShareDeletedEvent) {
			$this->shareDeleted($event);
		}
	}

	private function shareCreated(ShareCreatedEvent $event): void {
		$share = $event->getShare();

		$params = [
			'itemType' => $share->getNodeType(),
			'path' => $share->getNode()->getPath(),
			'itemSource' => $share->getNodeId(),
			'shareWith' => $share->getSharedWith(),
			'permissions' => $share->getPermissions(),
			'id' => $share->getId()
		];

		match ($share->getShareType()) {
			IShare::TYPE_LINK => $this->log(
				'The %s "%s" with ID "%s" has been shared via link with permissions "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'path',
					'itemSource',
					'permissions',
					'id',
				]
			),
			IShare::TYPE_USER => $this->log(
				'The %s "%s" with ID "%s" has been shared to the user "%s" with permissions "%s"  (Share ID: %s)',
				$params,
				[
					'itemType',
					'path',
					'itemSource',
					'shareWith',
					'permissions',
					'id',
				]
			),
			IShare::TYPE_GROUP => $this->log(
				'The %s "%s" with ID "%s" has been shared to the group "%s" with permissions "%s"  (Share ID: %s)',
				$params,
				[
					'itemType',
					'path',
					'itemSource',
					'shareWith',
					'permissions',
					'id',
				]
			),
			IShare::TYPE_ROOM => $this->log(
				'The %s "%s" with ID "%s" has been shared to the room "%s" with permissions "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'path',
					'itemSource',
					'shareWith',
					'permissions',
					'id',
				]
			),
			IShare::TYPE_EMAIL => $this->log(
				'The %s "%s" with ID "%s" has been shared to the email recipient "%s" with permissions "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'path',
					'itemSource',
					'shareWith',
					'permissions',
					'id',
				]
			),
			IShare::TYPE_CIRCLE => $this->log(
				'The %s "%s" with ID "%s" has been shared to the circle "%s" with permissions "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'path',
					'itemSource',
					'shareWith',
					'permissions',
					'id',
				]
			),
			IShare::TYPE_REMOTE => $this->log(
				'The %s "%s" with ID "%s" has been shared to the remote user "%s" with permissions "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'path',
					'itemSource',
					'shareWith',
					'permissions',
					'id',
				]
			),
			IShare::TYPE_REMOTE_GROUP => $this->log(
				'The %s "%s" with ID "%s" has been shared to the remote group "%s" with permissions "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'path',
					'itemSource',
					'shareWith',
					'permissions',
					'id',
				]
			),
			IShare::TYPE_DECK => $this->log(
				'The %s "%s" with ID "%s" has been shared to the deck card "%s" with permissions "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'path',
					'itemSource',
					'shareWith',
					'permissions',
					'id',
				]
			),
			IShare::TYPE_SCIENCEMESH => $this->log(
				'The %s "%s" with ID "%s" has been shared to the sciencemesh user "%s" with permissions "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'path',
					'itemSource',
					'shareWith',
					'permissions',
					'id',
				]
			),
			default => null
		};
	}

	private function shareDeleted(ShareDeletedEvent $event): void {
		$share = $event->getShare();

		$params = [
			'itemType' => $share->getNodeType(),
			'fileTarget' => $share->getTarget(),
			'itemSource' => $share->getNodeId(),
			'shareWith' => $share->getSharedWith(),
			'id' => $share->getId()
		];

		match ($share->getShareType()) {
			IShare::TYPE_LINK => $this->log(
				'The %s "%s" with ID "%s" has been unshared (Share ID: %s)',
				$params,
				[
					'itemType',
					'fileTarget',
					'itemSource',
					'id',
				]
			),
			IShare::TYPE_USER => $this->log(
				'The %s "%s" with ID "%s" has been unshared from the user "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'fileTarget',
					'itemSource',
					'shareWith',
					'id',
				]
			),
			IShare::TYPE_GROUP => $this->log(
				'The %s "%s" with ID "%s" has been unshared from the group "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'fileTarget',
					'itemSource',
					'shareWith',
					'id',
				]
			),
			IShare::TYPE_ROOM => $this->log(
				'The %s "%s" with ID "%s" has been unshared from the room "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'fileTarget',
					'itemSource',
					'shareWith',
					'id',
				]
			),
			IShare::TYPE_EMAIL => $this->log(
				'The %s "%s" with ID "%s" has been unshared from the email recipient "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'fileTarget',
					'itemSource',
					'shareWith',
					'id',
				]
			),
			IShare::TYPE_CIRCLE => $this->log(
				'The %s "%s" with ID "%s" has been unshared from the circle "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'fileTarget',
					'itemSource',
					'shareWith',
					'id',
				]
			),
			IShare::TYPE_REMOTE => $this->log(
				'The %s "%s" with ID "%s" has been unshared from the remote user "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'fileTarget',
					'itemSource',
					'shareWith',
					'id',
				]
			),
			IShare::TYPE_REMOTE_GROUP => $this->log(
				'The %s "%s" with ID "%s" has been unshared from the remote group "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'fileTarget',
					'itemSource',
					'shareWith',
					'id',
				]
			),
			IShare::TYPE_DECK => $this->log(
				'The %s "%s" with ID "%s" has been unshared from the deck card "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'fileTarget',
					'itemSource',
					'shareWith',
					'id',
				]
			),
			IShare::TYPE_SCIENCEMESH => $this->log(
				'The %s "%s" with ID "%s" has been unshared from the sciencemesh user "%s" (Share ID: %s)',
				$params,
				[
					'itemType',
					'fileTarget',
					'itemSource',
					'shareWith',
					'id',
				]
			),
			default => null
		};
	}
}
