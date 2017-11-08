<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
namespace OCA\DAV\Tests\unit;

use OCA\DAV\Server;
use OCP\IRequest;
use OCA\DAV\AppInfo\PluginManager;

/**
 * Class ServerTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit
 */
class ServerTest extends \Test\TestCase {

	public function test() {
		/** @var IRequest $r */
		$r = $this->createMock(IRequest::class);
		$s = new Server($r, '/');
		$this->assertInstanceOf('OCA\DAV\Server', $s);
	}
}
