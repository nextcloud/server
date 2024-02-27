<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Andreas Fischer <bantu@owncloud.com>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Files\Cache;

use OCP\Files\Cache\ICacheEntry;

class HomeCache extends Cache {
	/**
	 * get the size of a folder and set it in the cache
	 *
	 * @param string $path
	 * @param array|null|ICacheEntry $entry (optional) meta data of the folder
	 * @return int|float
	 */
	public function calculateFolderSize($path, $entry = null) {
		if ($path !== '/' and $path !== '' and $path !== 'files' and $path !== 'files_trashbin' and $path !== 'files_versions') {
			return parent::calculateFolderSize($path, $entry);
		} elseif ($path === '' or $path === '/') {
			// since the size of / isn't used (the size of /files is used instead) there is no use in calculating it
			return 0;
		} else {
			return $this->calculateFolderSizeInner($path, $entry, true);
		}
	}

	/**
	 * @param string $file
	 * @return ICacheEntry
	 */
	public function get($file) {
		$data = parent::get($file);
		if ($file === '' or $file === '/') {
			// only the size of the "files" dir counts
			$filesData = parent::get('files');

			if (isset($filesData['size'])) {
				$data['size'] = $filesData['size'];
			}
		}
		return $data;
	}
}
