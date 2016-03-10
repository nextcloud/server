<?php
/**
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
namespace OCA\Files_External_FTP;

use League\Flysystem\Adapter\Ftp as FtpAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\NotSupportedException;

class Adapter extends FtpAdapter {
	/**
	 * Set this to public
	 *
	 * {@inheritdoc}
	 */
	public function normalizeObject($item, $base) {
		return parent::normalizeObject($item, $base);
	}

	/**
	 * Add timestamp to result for folder mtime
	 *
	 * {@inheritdoc}
	 */
	protected function normalizeUnixObject($item, $base) {
		$item = preg_replace('#\s+#', ' ', $item, 7);
		list($permissions, /* $number */, /* $owner */, /* $group */, $size, $month, $day, $time, $name) = explode(' ', $item, 9);
		$type = $this->detectType($permissions);
		$path = empty($base) ? $name : $base . $this->separator . $name;
		$timestamp = strtotime($month . ' ' . $day . ' ' . $time);

		$permissions = $this->normalizePermissions($permissions);
		$visibility = $permissions & 0044 ? AdapterInterface::VISIBILITY_PUBLIC : AdapterInterface::VISIBILITY_PRIVATE;
		$size = (int)$size;

		return compact('type', 'path', 'visibility', 'size', 'timestamp');
	}
}
