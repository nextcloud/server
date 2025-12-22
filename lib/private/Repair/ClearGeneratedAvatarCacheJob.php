<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\Avatar\AvatarManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;

class ClearGeneratedAvatarCacheJob extends QueuedJob {
	public function __construct(
		ITimeFactory $timeFactory,
		protected AvatarManager $avatarManager,
	) {
		parent::__construct($timeFactory);
	}

	public function run($argument) {
		$this->avatarManager->clearCachedAvatars();
	}
}
