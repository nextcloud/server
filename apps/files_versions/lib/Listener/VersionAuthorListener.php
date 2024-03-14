<?php

declare(strict_types=1);

/**
 * @author Eduardo Morales emoral435@gmail.com>
 *
 * @license GNU AGPL-3.0-or-later
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
namespace OCA\Files_Versions\Listener;

use OC\Files\Node\Folder;
use OCA\Files_Versions\Versions\IMetadataVersionBackend;
use OCA\Files_Versions\Versions\IVersionManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\NodeWrittenEvent;
use OCP\Files\Node;
use OCP\IUserSession;

/** @template-implements IEventListener<NodeWrittenEvent> */
class VersionAuthorListener implements IEventListener {
	public function __construct(
		private IVersionManager $versionManager,
		private IUserSession $userSession,
	) {
	}

	/**
	 * @abstract handles events from a nodes version being changed
	 * @param Event $event the event that triggered this listener to activate
	 */
	public function handle(Event $event): void {
		if ($event instanceof NodeWrittenEvent) {
			$this->post_write_hook($event->getNode());
		}
	}

	/**
	 * @abstract handles the NodeWrittenEvent, and sets the metadata for the associated node
	 * @param Node $node the node that is currently being written
	 */
	public function post_write_hook(Node $node): void {
		$user = $this->userSession->getUser();
		// Do not handle folders or users that we cannot get metadata from
		if ($node instanceof Folder || is_null($user)) {
			return;
		}
		// check if our version manager supports setting the metadata
		if ($this->versionManager instanceof IMetadataVersionBackend) {
			$author = $user->getUID();
			$this->versionManager->setMetadataValue($node, $node->getMTime(), 'author', $author);
		}
	}
}
