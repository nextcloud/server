<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\DAV\Tests\Unit\AppInfo;

use OCA\Dav\AppInfo\Application;
use OCP\Contacts\IManager;
use Test\TestCase;

/**
 * Class ApplicationTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\AppInfo
 */
class ApplicationTest extends TestCase {
	public function test() {
		$app = new Application();

		// assert service instances in the container are properly setup
		$s = $app->getContainer()->query('ContactsManager');
		$this->assertInstanceOf('OCA\DAV\CardDAV\ContactsManager', $s);
		$s = $app->getContainer()->query('CardDavBackend');
		$this->assertInstanceOf('OCA\DAV\CardDAV\CardDavBackend', $s);

		// assert setupContactsProvider() is proper
		/** @var IManager | \PHPUnit_Framework_MockObject_MockObject $cm */
		$cm = $this->getMockBuilder('OCP\Contacts\IManager')->disableOriginalConstructor()->getMock();
		$app->setupContactsProvider($cm, 'user01');
		$this->assertTrue(true);
	}
}
