<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\Calendar\Resource\IManager as IResourceManager;
use OCP\Calendar\Room\IManager as IRoomManager;

class UpdateCalendarResourcesRoomsBackgroundJob extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		private IResourceManager $resourceManager,
		private IRoomManager $roomManager,
	) {
		parent::__construct($time);

		// Run once an hour
		$this->setInterval(60 * 60);
		$this->setTimeSensitivity(self::TIME_SENSITIVE);
	}

	public function run($argument): void {
		$this->resourceManager->update();
		$this->roomManager->update();
	}
}
