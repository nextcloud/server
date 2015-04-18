<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\Files_Sharing;

use OC\Files\Cache\Cache;

class ReadOnlyCache extends Cache {
	public function get($path) {
		$data = parent::get($path);
		if ($data !== false) {
			$data['permissions'] &= (\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_SHARE);
		}
		return $data;
	}

	public function getFolderContents($path) {
		$content = parent::getFolderContents($path);
		foreach ($content as &$data) {
			$data['permissions'] &= (\OCP\Constants::PERMISSION_READ | \OCP\Constants::PERMISSION_SHARE);
		}
		return $content;
	}
}
