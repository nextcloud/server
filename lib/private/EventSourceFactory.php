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

namespace OC;

use OCP\IEventSource;
use OCP\IEventSourceFactory;
use OCP\IRequest;

class EventSourceFactory implements IEventSourceFactory {
	private IRequest $request;


	public function __construct(IRequest $request) {
		$this->request = $request;
	}

	/**
	 * Create a new event source
	 *
	 * @return IEventSource
	 * @since 28.0.0
	 */
	public function create(): IEventSource {
		return new \OC_EventSource($this->request);
	}
}
