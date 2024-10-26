<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Upload;

use OCA\DAV\BackgroundJob\UploadCleanup;
use OCP\BackgroundJob\IJobList;
use OCP\IUserSession;

class CleanupService {
	public function __construct(
		private IUserSession $userSession,
		private IJobList $jobList,
	) {
	}

	public function addJob(string $folder) {
		$this->jobList->add(UploadCleanup::class, ['uid' => $this->userSession->getUser()->getUID(), 'folder' => $folder]);
	}

	public function removeJob(string $folder) {
		$this->jobList->remove(UploadCleanup::class, ['uid' => $this->userSession->getUser()->getUID(), 'folder' => $folder]);
	}
}
