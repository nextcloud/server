<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Upload;

use OCA\DAV\BackgroundJob\UploadCleanup;
use OCP\BackgroundJob\IJobList;

class CleanupService {
	public function __construct(
		private IJobList $jobList,
	) {
	}

	public function addJob(string $uid, string $folder) {
		$this->jobList->add(UploadCleanup::class, ['uid' => $uid, 'folder' => $folder]);
	}

	public function removeJob(string $uid, string $folder) {
		$this->jobList->remove(UploadCleanup::class, ['uid' => $uid, 'folder' => $folder]);
	}
}
