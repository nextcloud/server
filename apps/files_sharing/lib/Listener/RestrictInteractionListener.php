<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files_Sharing\Listener;

use OCP\Constants;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\InteractionRestrictedException;
use OCP\Interaction\Receivers\EmailReceiver;
use OCP\Interaction\Receivers\LinkReceiver;
use OCP\Interaction\Resources\NodeResource;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\Share\IManager;

/**
 * @template-implements IEventListener<RestrictInteractionEvent>
 */
final readonly class RestrictInteractionListener implements IEventListener {

	public function __construct(
		private IRootFolder $rootFolder,
		private IManager $manager,
	) {
	}

	/**
	 * @param RestrictInteractionEvent $event
	 */
	#[\Override]
	public function handle(Event $event): void {
		if ($event->resource instanceof NodeResource && $event->action instanceof ShareAction) {
			$nodePermissions = $event->resource->getNodePermissions();

			if (($nodePermissions & Constants::PERMISSION_SHARE) !== Constants::PERMISSION_SHARE) {
				throw new InteractionRestrictedException('No share permission on the node.');
			}

			$userFolder = $this->rootFolder->getUserFolder($event->userId);
			if ($event->resource->nodeId === $userFolder->getId()) {
				throw new InteractionRestrictedException('Cannot share home folder node.');
			}

			if ($event->action->filesSharingPermissions !== null) {
				if (($event->action->filesSharingPermissions & ~$nodePermissions) !== 0) {
					throw new InteractionRestrictedException('Cannot share node with more permissions than the node already has.');
				}

				if ($event->resource->getNode() instanceof File) {
					if (($event->action->filesSharingPermissions & Constants::PERMISSION_DELETE) === Constants::PERMISSION_DELETE) {
						throw new InteractionRestrictedException('Cannot share file node with delete permission.');
					}

					if (($event->action->filesSharingPermissions & Constants::PERMISSION_CREATE) === Constants::PERMISSION_CREATE) {
						throw new InteractionRestrictedException('Cannot share file node with create permission.');
					}
				}

				if (!$event->receiver instanceof LinkReceiver
					&& !$event->receiver instanceof EmailReceiver
					&& ($event->action->filesSharingPermissions & Constants::PERMISSION_READ) !== Constants::PERMISSION_READ) {
					throw new InteractionRestrictedException('No read permission on the share.');
				}

				if (($event->receiver instanceof LinkReceiver || $event->receiver instanceof EmailReceiver)
					&& $event->resource->getNode() instanceof Folder
					&& ($event->action->filesSharingPermissions & (Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE)) !== 0
					&& !$this->manager->shareApiLinkAllowPublicUpload()) {
					throw new InteractionRestrictedException('Public upload is not allowed.');
				}
			}
		}
	}
}
