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
	 * @param array $entry (optional) meta data of the folder
	 * @return int
	 */
	public function calculateFolderSize($path, $entry = null) {
		if ($path !== '/' and $path !== '' and $path !== 'files' and $path !== 'files_trashbin' and $path !== 'files_versions') {
			return parent::calculateFolderSize($path, $entry);
		} elseif ($path === '' or $path === '/') {
			// since the size of / isn't used (the size of /files is used instead) there is no use in calculating it
			return 0;
		}

		$totalSize = 0;
		if (is_null($entry)) {
			$entry = $this->get($path);
		}
		if ($entry && $entry['mimetype'] === 'httpd/unix-directory') {
			$id = $entry['fileid'];
			$sql = 'SELECT SUM(`size`) AS f1, ' .
			   'SUM(`unencrypted_size`) AS f2 FROM `*PREFIX*filecache` ' .
				'WHERE `parent` = ? AND `storage` = ? AND `size` >= 0';
			$result = \OC_DB::executeAudited($sql, array($id, $this->getNumericStorageId()));
			if ($row = $result->fetchRow()) {
				$result->closeCursor();
				list($sum, $unencryptedSum) = array_values($row);
				$totalSize = 0 + $sum;
				$unencryptedSize = 0 + $unencryptedSum;
				$entry['size'] += 0;
				if (!isset($entry['unencrypted_size'])) {
					$entry['unencrypted_size'] = 0;
				}
				$entry['unencrypted_size'] += 0;
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

	/**
	 * @param string $path
	 * @return array
	 */
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
