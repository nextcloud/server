<?php
/**
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

namespace OCA\DAV\Tests\unit\CalDAV;

use OCA\DAV\CalDAV\Outbox;
use OCP\IConfig;
use Test\TestCase;

class OutboxTest extends TestCase {

	/** @var IConfig */
	private $config;

	/** @var Outbox */
	private $outbox;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->outbox = new Outbox($this->config, 'user-principal-123');
	}

	public function testGetACLFreeBusyEnabled() {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'disableFreeBusy', 'no')
			->will($this->returnValue('no'));

		$this->assertEquals([
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123/calendar-proxy-read',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send',
				'principal' => 'user-principal-123',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send',
				'principal' => 'user-principal-123/calendar-proxy-write',
				'protected' => true,
			],
		], $this->outbox->getACL());
	}

	public function testGetACLFreeBusyDisabled() {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'disableFreeBusy', 'no')
			->will($this->returnValue('yes'));

		$this->assertEquals([
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123/calendar-proxy-read',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => 'user-principal-123/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send-invite',
				'principal' => 'user-principal-123',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send-invite',
				'principal' => 'user-principal-123/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send-reply',
				'principal' => 'user-principal-123',
				'protected' => true,
			],
			[
				'privilege' => '{urn:ietf:params:xml:ns:caldav}schedule-send-reply',
				'principal' => 'user-principal-123/calendar-proxy-write',
				'protected' => true,
			],
		], $this->outbox->getACL());
	}
}
