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
		if ($path !== '/' and $path !== '' and $path !== 'files') {
			return parent::calculateFolderSize($path);
		}

		$totalSize = 0;
		$entry = $this->get($path);
		if ($entry && $entry['mimetype'] === 'httpd/unix-directory') {
			$id = $entry['fileid'];
			$sql = 'SELECT SUM(`size`) AS f1, ' .
			   'SUM(`unencrypted_size`) AS f2 FROM `*PREFIX*filecache` ' .
				'WHERE `parent` = ? AND `storage` = ? AND `size` >= 0';
			$result = \OC_DB::executeAudited($sql, array($id, $this->getNumericStorageId()));
			if ($row = $result->fetchRow()) {
				list($sum, $unencryptedSum) = array_values($row);
				$totalSize = (int)$sum;
				$unencryptedSize = (int)$unencryptedSum;
				if ($entry['size'] !== $totalSize) {
					$this->update($id, array('size' => $totalSize));
				}
				if ($entry['unencrypted_size'] !== $unencryptedSize) {
					$this->update($id, array('unencrypted_size' => $unencryptedSize));
				}
			}
		}
		return $totalSize;
	}

	public function get($path) {
		$data = parent::get($path);
		if ($path === '' or $path === '/') {
			// only the size of the "files" dir counts
			$filesData = parent::get('files');

			if (isset($filesData['size'])) {
				$data['size'] = $filesData['size'];
			}
		}
		return $data;
	}
}
