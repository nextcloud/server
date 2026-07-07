<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\AppInfo\Application;
use OCP\Constants;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\Interaction\Actions\ShareAction;
use OCP\Interaction\InteractionRestrictedException;
use OCP\Interaction\Receivers\EmailReceiver;
use OCP\Interaction\Receivers\LinkReceiver;
use OCP\Interaction\Resources\NodeResource;
use OCP\Interaction\RestrictInteractionEvent;
use OCP\L10N\IFactory;
use OCP\Share\IManager;

/**
 * @template-implements IEventListener<RestrictInteractionEvent>
 */
final class RestrictInteractionListener implements IEventListener {
	private readonly IL10N $l10n;

	public function __construct(
		private IRootFolder $rootFolder,
		private IManager $manager,
		IFactory $l10nFactory,
	) {
		$this->l10n = $l10nFactory->get(Application::APP_ID);
	}

	/**
	 * @param RestrictInteractionEvent $event
	 */
	#[\Override]
	public function handle(Event $event): void {
		if ($event->resource instanceof NodeResource && $event->action instanceof ShareAction) {
			if (!$event->resource->getNode()->isShareable()) {
				throw new InteractionRestrictedException('Node is not shareable.', $this->l10n->t('You are not allowed to share "%s".', [$event->resource->getNode()->getName()]));
			}

			$userFolder = $this->rootFolder->getUserFolder($event->userId);
			if ($event->resource->nodeId === $userFolder->getId()) {
				throw new InteractionRestrictedException('Cannot share home folder node.', $this->l10n->t('You cannot share your home folder.'));
			}

			if ($event->action->filesSharingPermissions !== null) {
				if (($event->action->filesSharingPermissions & ~$event->resource->getNodePermissions()) !== 0) {
					$path = $userFolder->getRelativePath($event->resource->getNode()->getPath());
					throw new InteractionRestrictedException('Cannot share node with more permissions than the node already has.', $this->l10n->t('You cannot share "%s" with more permission than you have yourself.', [$path]));
				}

				if ($event->resource->getNode() instanceof File) {
					if (($event->action->filesSharingPermissions & Constants::PERMISSION_DELETE) === Constants::PERMISSION_DELETE) {
						throw new InteractionRestrictedException('Cannot share file node with delete permission.', $this->l10n->t('File cannot be shared with delete permission.'));
					}

					if (($event->action->filesSharingPermissions & Constants::PERMISSION_CREATE) === Constants::PERMISSION_CREATE) {
						throw new InteractionRestrictedException('Cannot share file node with create permission.', $this->l10n->t('File cannot be shared with create permission.'));
					}
				}

				if (!$event->receiver instanceof LinkReceiver
					&& !$event->receiver instanceof EmailReceiver
					&& ($event->action->filesSharingPermissions & Constants::PERMISSION_READ) !== Constants::PERMISSION_READ) {
					throw new InteractionRestrictedException('No read permission on the share.', $this->l10n->t('File share needs at least read permission.'));
				}

				if (($event->receiver instanceof LinkReceiver || $event->receiver instanceof EmailReceiver)
					&& $event->resource->getNode() instanceof Folder
					&& ($event->action->filesSharingPermissions & (Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE)) !== 0
					&& !$this->manager->shareApiLinkAllowPublicUpload()) {
					throw new InteractionRestrictedException('Public upload is not allowed.', $this->l10n->t('Public upload is not allowed.'));
				}
			}
		}
	}
}
