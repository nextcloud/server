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
namespace OCA\DAV\Tests\unit\CalDAV\Repair;

use OCA\DAV\CalDAV\Repair\IRepairStep;
use OCA\DAV\CalDAV\Repair\Plugin;
use OCA\DAV\CalDAV\Repair\RepairStepFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\CalDAV\ICalendarObject;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Component;
use Sabre\VObject\Component\VCalendar;
use Test\TestCase;

class PluginTest extends TestCase {

	private RequestInterface|MockObject $request;
	private ResponseInterface|MockObject $response;
	private Tree|MockObject $tree;
	private IRepairStep|MockObject $repairStep;

	private Plugin $plugin;

	protected function setUp(): void {
		parent::setUp();
		$this->request = $this->createMock(RequestInterface::class);
		$this->response = $this->createMock(ResponseInterface::class);
		$server = $this->createMock(Server::class);
		$this->tree = $this->createMock(Tree::class);
		$server->tree = $this->tree;
		$this->repairStep = $this->createMock(IRepairStep::class);
		$this->repairStepFactory = new RepairStepFactory();
		$this->repairStepFactory->addRepairStep($this->repairStep);

		$this->plugin = new Plugin($this->repairStepFactory);
		$this->plugin->initialize($server);
	}

	/**
	 * @dataProvider dataForTestRunRepairStepsOnCalendarData
	 */
	public function testRunRepairStepsOnCalendarData(VCalendar $VCalendar, ?VCalendar $oldVCalendar, bool $modified, bool $isNew, bool $repairStepRunOnCreate): void {
		$modifiedChanged = false;
		$this->repairStep->expects($this->once())->method('runOnCreate')->willReturn($repairStepRunOnCreate);
		$this->repairStep->expects($this->once())->method('onCalendarObjectChange')->with(self::callback(function (?VCalendar $value) use ($oldVCalendar) {
			// Can't simply check object equality because of missing references to parents, so checking the serialized value
			self::assertSame($oldVCalendar?->serialize(), $value?->serialize());
			return true;
		}), self::callback(function (VCalendar $value) use ($VCalendar) {
			self::assertSame($VCalendar->serialize(), $value->serialize());
			return true;
		}), $modifiedChanged);
		$node = $this->createMock(ICalendarObject::class);
		$node->expects($isNew ? $this->never() : $this->once())->method('get')->willReturn($oldVCalendar?->serialize());
		$this->request->expects($isNew ? $this->never() : $this->once())->method('getPath')->willReturn('/a-path');
		$this->tree->expects($isNew ? $this->never() : $this->once())->method('getNodeForPath')->with('/a-path')->willReturn($node);
		$this->plugin->calendarObjectChange($this->request, $this->response, $VCalendar, '', $modifiedChanged, $isNew);
		self::assertSame($modified, $modifiedChanged);
	}

	public function dataForTestRunRepairStepsOnCalendarData(): array {

		$vCalendar = new VCalendar();
		$oldVCalendar = new VCalendar();

		$vCalendar->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting',
			'DTSTART' => new \DateTime('2016-01-01 00:00:00'),
		]);

		$oldVCalendar->add('VEVENT', [
			'UID' => 'uid-1234',
			'LAST-MODIFIED' => 123456,
			'SEQUENCE' => 2,
			'SUMMARY' => 'Fellowship meeting updated',
			'DTSTART' => new \DateTime('2018-01-01 00:00:00'),
		]);

		return [
			[$vCalendar, null, false, true, true],
			[$vCalendar, $oldVCalendar, false, false, false]
		];
	}
}
