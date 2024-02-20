<?php
/**
 * @copyright 2023, Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
 * @license AGPL-3.0
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
namespace OCA\DAV\CalDAV\Repair;

use Sabre\CalDAV\ICalendarObject;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

class Plugin extends ServerPlugin {

	private Server $server;

	public function __construct(private RepairStepFactory $repairStepFactory) {	}

	/**
	 * Returns the name of the plugin.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using Server::getPlugin
	 */
	public function getPluginName(): string {
		return 'nc-caldav-repair';
	}

	public function initialize(Server $server): void {
		$this->server = $server;
		$server->on('calendarObjectChange', [$this, 'calendarObjectChange']);
	}

	public function calendarObjectChange(RequestInterface $request, ResponseInterface $response, VCalendar $vCal, string $calendarPath, bool &$modified, bool $isNew): void {
			foreach ($this->repairStepFactory->getRepairSteps() as $repairStep) {
				if ($repairStep->runOnCreate() && $isNew) {
					$repairStep->onCalendarObjectChange(null, $vCal, $modified);
				} else if (!$isNew) {
					try {
						/** @var ICalendarObject $node */
						$node = $this->server->tree->getNodeForPath($request->getPath());
						/** @var VCalendar $oldObj */
						$oldObj = Reader::read($node->get());
						$repairStep->onCalendarObjectChange($oldObj, $vCal, $modified);
					} catch (NotFound) {
						// Nothing, we just skip
					}
				}
			}

	}
}
