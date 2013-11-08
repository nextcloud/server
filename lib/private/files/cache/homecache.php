<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Cache;

class HomeCache extends Cache {
	/**
	 * get the size of a folder and set it in the cache
	 *
	 * @param string $path
	 * @return int
	 */
	public function calculateFolderSize($path) {
		$totalSize = 0;
		$entry = $this->get($path);
		if ($entry && $entry['mimetype'] === 'httpd/unix-directory') {
			$isRoot = ($path === '/' or $path === '');
			$id = $entry['fileid'];
			$sql = 'SELECT SUM(`size`), MIN(`size`) FROM `*PREFIX*filecache` ' .
				'WHERE `parent` = ? AND `storage` = ?';
			if ($isRoot) {
				// filter out non-scanned dirs at the root
				$sql .= ' AND `size` >= 0';
			}
			$result = \OC_DB::executeAudited($sql, array($id, $this->getNumericStorageId()));
			if ($row = $result->fetchRow()) {
				list($sum, $min) = array_values($row);
				$sum = (int)$sum;
				$min = (int)$min;
				if ($min === -1) {
					$totalSize = $min;
				} else {
					$totalSize = $sum;
				}
				if ($entry['size'] !== $totalSize) {
					$this->update($id, array('size' => $totalSize));
				}

			}
		}
		return $totalSize;
	}
}
