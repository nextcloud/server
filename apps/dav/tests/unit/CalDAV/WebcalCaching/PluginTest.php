<?php
/**
 * @copyright Copyright (c) 2018 Georg Ehrke
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

namespace OCA\DAV\Tests\unit\CalDAV\WebcalCaching;

use OCA\DAV\CalDAV\WebcalCaching\Plugin;
use OCP\IRequest;

class PluginTest extends \Test\TestCase {

	public function testDisabled() {
		$request = $this->createMock(IRequest::class);
		$request->expects($this->at(0))
			->method('isUserAgent')
			->with([])
			->will($this->returnValue(false));

		$request->expects($this->at(1))
			->method('getHeader')
			->with('X-NC-CalDAV-Webcal-Caching')
			->will($this->returnValue(''));

		$plugin = new Plugin($request);

		$this->assertEquals(false, $plugin->isCachingEnabledForThisRequest());
	}

	public function testEnabled() {
		$request = $this->createMock(IRequest::class);
		$request->expects($this->at(0))
			->method('isUserAgent')
			->with([])
			->will($this->returnValue(false));

		$request->expects($this->at(1))
			->method('getHeader')
			->with('X-NC-CalDAV-Webcal-Caching')
			->will($this->returnValue('On'));

		$plugin = new Plugin($request);

		$this->assertEquals(true, $plugin->isCachingEnabledForThisRequest());
	}
}
