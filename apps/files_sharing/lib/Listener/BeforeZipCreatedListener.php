<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Listener;

use OCA\Files_Sharing\Services\ShareAccessService;
use OCP\Activity\IManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\BeforeZipCreatedEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\ISharedStorage;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\L10N\IFactory;

/**
 * @template-implements IEventListener<BeforeZipCreatedEvent|Event>
 */
class BeforeZipCreatedListener implements IEventListener {

	public function __construct(
		private IUserSession $userSession,
		private IRootFolder $rootFolder,
		private IManager $activityManager,
		private IRequest $request,
		private IFactory $l10n,
		private ShareAccessService $accessService,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeZipCreatedEvent)) {
			return;
		}

		// The view-only handling is already managed by the DAV plugin
		// so all we need to do is to ensure that the share is not a "hide-download" share where we also forbid downloading

		$folder = $event->getFolder();
		if ($folder === null) {
			$user = $this->userSession->getUser();
			if ($user === null) {
				return;
			} else {
				$folder = $this->rootFolder->getUserFolder($user->getUID())->get($event->getDirectory());
				assert($folder instanceof Folder, 'Directory is not a folder but a file');
			}
		}

		$files = $event->getFiles();
		if (empty($files)) {
			$files = [$folder];
		} else {
			$files = array_map(fn (string $path) => $folder->get($path), $files);
		}

		$notified = false;
		foreach ($files as $file) {
			$storage = $file->getStorage();
			if ($storage->instanceOfStorage(ISharedStorage::class)) {
				/** @var ISharedStorage $storage */
				$share = $storage->getShare();
				// Check if it is allowed to download this file - if "hide-download" is enabled but a zip file is created
				// it means the users managed to access the endpoint manually -> block it
				if ($share->getHideDownload()) {
					$event->setSuccessful(false);
					$event->setErrorMessage($this->l10n->get('files_sharing')->t('Download permission of share not granted.'));
					// we can early return now as the event is set to failed state
					return;
				}

				// only notify once for the zip download
				if ($notified == false) {
					$this->accessService->shareDownloaded($share);
					$notified = true;
				}

				// All we now need to do is log the download
				$this->accessService->sharedFileDownloaded($share, $file);
			}
		}
	}

}
