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
	/** @var IUserSession */
	private $userSession;
	/** @var IJobList */
	private $jobList;

	public function __construct(IUserSession $userSession, IJobList $jobList) {
		$this->userSession = $userSession;
		$this->jobList = $jobList;
	}

	public function addJob(string $folder) {
		$this->jobList->add(UploadCleanup::class, ['uid' => $this->userSession->getUser()->getUID(), 'folder' => $folder]);
	}

	public function removeJob(string $folder) {
		$this->jobList->remove(UploadCleanup::class, ['uid' => $this->userSession->getUser()->getUID(), 'folder' => $folder]);
	}
}
