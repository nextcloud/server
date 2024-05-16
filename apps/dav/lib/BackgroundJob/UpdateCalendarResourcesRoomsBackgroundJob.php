<?php

declare(strict_types=1);

/**
 * @copyright 2019, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
