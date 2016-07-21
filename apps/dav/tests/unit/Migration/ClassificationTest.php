<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Tests\unit\DAV\Migration;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Migration\Classification;
use OCA\DAV\Tests\unit\CalDAV\AbstractCalDavBackendTest;
use OCP\IUser;

/**
 * Class ClassificationTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\DAV
 */
class ClassificationTest extends AbstractCalDavBackendTest {

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCP\IUserManager */
	private $userManager;

	public function setUp() {
		parent::setUp();

		$this->userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()->getMock();
	}

	public function test() {
		// setup data
		$calendarId = $this->createTestCalendar();
		$eventUri = $this->createEvent($calendarId, '20130912T130000Z', '20130912T140000Z');
		$object = $this->backend->getCalendarObject($calendarId, $eventUri);

		// assert proper classification
		$this->assertEquals(CalDavBackend::CLASSIFICATION_PUBLIC, $object['classification']);
		$this->backend->setClassification($object['id'], CalDavBackend::CLASSIFICATION_CONFIDENTIAL);
		$object = $this->backend->getCalendarObject($calendarId, $eventUri);
		$this->assertEquals(CalDavBackend::CLASSIFICATION_CONFIDENTIAL, $object['classification']);

		// run migration
		$c = new Classification($this->backend, $this->userManager);

		/** @var IUser | \PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->once())->method('getUID')->willReturn('caldav-unit-test');

		$c->runForUser($user);

		// assert classification after migration
		$object = $this->backend->getCalendarObject($calendarId, $eventUri);
		$this->assertEquals(CalDavBackend::CLASSIFICATION_PUBLIC, $object['classification']);
	}
}
