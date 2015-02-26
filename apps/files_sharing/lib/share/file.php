<?php
/**
* ownCloud
*
* @author Bjoern Schiessle, Michael Gapczynski
* @copyright 2012 Michael Gapczynski <mtgap@owncloud.com>
 *           2014 Bjoern Schiessle <schiessle@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*/

class OC_Share_Backend_File implements OCP\Share_Backend_File_Dependent {

	const FORMAT_SHARED_STORAGE = 0;
	const FORMAT_GET_FOLDER_CONTENTS = 1;
	const FORMAT_FILE_APP_ROOT = 2;
	const FORMAT_OPENDIR = 3;
	const FORMAT_GET_ALL = 4;
	const FORMAT_PERMISSIONS = 5;
	const FORMAT_TARGET_NAMES = 6;

	private $path;

	public function isValidSource($itemSource, $uidOwner) {
		$path = \OC\Files\Filesystem::getPath($itemSource);
		if ($path) {
			// FIXME: attributes should not be set here,
			// keeping this pattern for now to avoid unexpected
			// regressions
			$this->path = \OC\Files\Filesystem::normalizePath(basename($path));
			return true;
		}
		return false;
	}

	public function getFilePath($itemSource, $uidOwner) {
		if (isset($this->path)) {
			$path = $this->path;
			$this->path = null;
			return $path;
		} else {
			$path = \OC\Files\Filesystem::getPath($itemSource);
			if ($path) {
				return $path;
			}
		}
		return false;
	}

	/**
	 * create unique target
	 * @param string $filePath
	 * @param string $shareWith
	 * @param array $exclude (optional)
	 * @return string
	 */
	public function generateTarget($filePath, $shareWith, $exclude = null) {
		$shareFolder = \OCA\Files_Sharing\Helper::getShareFolder();
		$target = \OC\Files\Filesystem::normalizePath($shareFolder . '/' . basename($filePath));

		// for group shares we return the target right away
		if ($shareWith === false) {
			return $target;
		}

		\OC\Files\Filesystem::initMountPoints($shareWith);
		$view = new \OC\Files\View('/' . $shareWith . '/files');

		if (!$view->is_dir($shareFolder)) {
			$dir = '';
			$subdirs = explode('/', $shareFolder);
			foreach ($subdirs as $subdir) {
				$dir = $dir . '/' . $subdir;
				if (!$view->is_dir($dir)) {
					$view->mkdir($dir);
				}
			}
		}

		$excludeList = (is_array($exclude)) ? $exclude : array();

		return \OCA\Files_Sharing\Helper::generateUniqueTarget($target, $excludeList, $view);
	}

	public function formatItems($items, $format, $parameters = null) {
		if ($format == self::FORMAT_SHARED_STORAGE) {
			// Only 1 item should come through for this format call
			$item = array_shift($items);
			return array(
				'parent' => $item['parent'],
				'path' => $item['path'],
				'storage' => $item['storage'],
				'permissions' => $item['permissions'],
				'uid_owner' => $item['uid_owner'],
			);
		} else if ($format == self::FORMAT_GET_FOLDER_CONTENTS) {
			$files = array();
			foreach ($items as $item) {
				$file = array();
				$file['fileid'] = $item['file_source'];
				$file['storage'] = $item['storage'];
				$file['path'] = $item['file_target'];
				$file['parent'] = $item['file_parent'];
				$file['name'] = basename($item['file_target']);
				$file['mimetype'] = $item['mimetype'];
				$file['mimepart'] = $item['mimepart'];
				$file['mtime'] = $item['mtime'];
				$file['encrypted'] = $item['encrypted'];
				$file['etag'] = $item['etag'];
				$file['uid_owner'] = $item['uid_owner'];
				$file['displayname_owner'] = $item['displayname_owner'];

				$storage = \OC\Files\Filesystem::getStorage('/');
				$cache = $storage->getCache();
				if ($item['encrypted'] or ($item['unencrypted_size'] > 0 and $cache->getMimetype($item['mimetype']) === 'httpd/unix-directory')) {
					$file['size'] = $item['unencrypted_size'];
					$file['encrypted_size'] = $item['size'];
				} else {
					$file['size'] = $item['size'];
				}
				$files[] = $file;
			}
			return $files;
		} else if ($format == self::FORMAT_OPENDIR) {
			$files = array();
			foreach ($items as $item) {
				$files[] = basename($item['file_target']);
			}
			return $files;
		} else if ($format == self::FORMAT_GET_ALL) {
			$ids = array();
			foreach ($items as $item) {
				$ids[] = $item['file_source'];
			}
			return $ids;
		} else if ($format === self::FORMAT_PERMISSIONS) {
			$filePermissions = array();
			foreach ($items as $item) {
				$filePermissions[$item['file_source']] = $item['permissions'];
			}
			return $filePermissions;
		} else if ($format === self::FORMAT_TARGET_NAMES) {
			$targets = array();
			foreach ($items as $item) {
				$targets[] = $item['file_target'];
			}
			return $targets;
		}
		return array();
	}

	/**
	 * check if server2server share is enabled
	 *
	 * @param int $shareType
	 * @return boolean
	 */
	public function isShareTypeAllowed($shareType) {
		if ($shareType === \OCP\Share::SHARE_TYPE_REMOTE) {
			return \OCA\Files_Sharing\Helper::isOutgoingServer2serverShareEnabled();
		}

		return true;
	}

	/**
	 * resolve reshares to return the correct source item
	 * @param array $source
	 * @return array source item
	 */
	protected static function resolveReshares($source) {
		if (isset($source['parent'])) {
			$parent = $source['parent'];
			while (isset($parent)) {
				$query = \OC_DB::prepare('SELECT `parent`, `uid_owner` FROM `*PREFIX*share` WHERE `id` = ?', 1);
				$item = $query->execute(array($parent))->fetchRow();
				if (isset($item['parent'])) {
					$parent = $item['parent'];
				} else {
					$fileOwner = $item['uid_owner'];
					break;
				}
			}
		} else {
			$fileOwner = $source['uid_owner'];
		}
		if (isset($fileOwner)) {
			$source['fileOwner'] = $fileOwner;
		} else {
			\OCP\Util::writeLog('files_sharing', "No owner found for reshare", \OCP\Util::ERROR);
		}

		return $source;
	}

	/**
	 * @param string $target
	 * @param string $mountPoint
	 * @param string $itemType
	 * @return array|false source item
	 */
	public static function getSource($target, $mountPoint, $itemType) {
		if ($itemType === 'folder') {
			$source = \OCP\Share::getItemSharedWith('folder', $mountPoint, \OC_Share_Backend_File::FORMAT_SHARED_STORAGE);
			if ($source && $target !== '') {
				// note: in case of ext storage mount points the path might be empty
				// which would cause a leading slash to appear
				$source['path'] = ltrim($source['path'] . '/' . $target, '/');
			}
		} else {
			$source = \OCP\Share::getItemSharedWith('file', $mountPoint, \OC_Share_Backend_File::FORMAT_SHARED_STORAGE);
		}
		if ($source) {
			return self::resolveReshares($source);
		}

		\OCP\Util::writeLog('files_sharing', 'File source not found for: '.$target, \OCP\Util::DEBUG);
		return false;
	}

}
