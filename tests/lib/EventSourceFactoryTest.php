<?php
/**
 * @copyright Copyright (c) 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license AGPL-3.0-or-later
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

namespace Test;

use OC\EventSourceFactory;
use OCP\IEventSource;
use OCP\IRequest;

class EventSourceFactoryTest extends TestCase {
	public function testCreate(): void {
		$request = $this->createMock(IRequest::class);
		$factory = new EventSourceFactory($request);

		$instance = $factory->create();
		$this->assertInstanceOf(IEventSource::class, $instance);
	}
}
