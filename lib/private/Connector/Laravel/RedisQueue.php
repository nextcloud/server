<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace OC\Connector\Laravel;

use Illuminate\Container\Container;
use Predis\Client;
use Illuminate\Queue\Capsule\Manager as Queue;

class RedisQueueFactory {
	public function getQueue(Client $client) {
		$container = new Container();
		$container->singleton('redis', function() use ($client) {
			return new RedisDatabase($client);
		});

		return new Queue($container);
	}
}
