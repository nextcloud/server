<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace SearchDAV\DAV;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;

class PathHelper {
	/** @var Server */
	private $server;

	/**
	 * PathHelper constructor.
	 *
	 * @param Server $server
	 */
	public function __construct(Server $server) {
		$this->server = $server;
	}

	public function getPathFromUri(string $uri): ?string {
		if (strpos($uri, '://') === false) {
			return $uri;
		}
		try {
			return ($uri === '' && $this->server->getBaseUri() === '/') ? '' : $this->server->calculateUri($uri);
		} catch (Forbidden $e) {
			return null;
		}
	}
}
