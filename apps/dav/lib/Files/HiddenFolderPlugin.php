<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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

namespace OCA\DAV\Files;

use OC\Files\Filesystem;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class HiddenFolderPlugin extends ServerPlugin {
	public function initialize(Server $server): void {
		$server->on('beforeBind', [$this, 'onBind'], 1000);
		$server->on('beforeUnbind', [$this, 'onBind'], 1000);
	}

	public function onBind($path): bool {
		$hiddenName = Filesystem::getHiddenFolderName();
		if (basename($path) === $hiddenName) {
			throw new Forbidden("Can't modify hidden base folder");
		}
		return true;
	}
}
