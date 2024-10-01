<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\ShareBackend;

use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class File implements \OCP\Share_Backend_File_Dependent {
	public const FORMAT_SHARED_STORAGE = 0;
	public const FORMAT_GET_FOLDER_CONTENTS = 1;
	public const FORMAT_FILE_APP_ROOT = 2;
	public const FORMAT_OPENDIR = 3;
	public const FORMAT_GET_ALL = 4;
	public const FORMAT_PERMISSIONS = 5;
	public const FORMAT_TARGET_NAMES = 6;

	private $path;

	/** @var FederatedShareProvider */
	private $federatedShareProvider;

	public function __construct(?FederatedShareProvider $federatedShareProvider = null) {
		if ($federatedShareProvider) {
			$this->federatedShareProvider = $federatedShareProvider;
		} else {
			$this->federatedShareProvider = \OC::$server->query(FederatedShareProvider::class);
		}
	}

	public function isValidSource($itemSource, $uidOwner) {
		try {
			$path = \OC\Files\Filesystem::getPath($itemSource);
			// FIXME: attributes should not be set here,
			// keeping this pattern for now to avoid unexpected
			// regressions
			$this->path = \OC\Files\Filesystem::normalizePath(basename($path));
			return true;
		} catch (\OCP\Files\NotFoundException $e) {
			return false;
		}
	}

	public function getFilePath($itemSource, $uidOwner) {
		if (isset($this->path)) {
			$path = $this->path;
			$this->path = null;
			return $path;
		} else {
			try {
				$path = \OC\Files\Filesystem::getPath($itemSource);
				return $path;
			} catch (\OCP\Files\NotFoundException $e) {
				return false;
			}
		}
	}

	/**
	 * create unique target
	 *
	 * @param string $itemSource
	 * @param string $shareWith
	 * @return string
	 */
	public function generateTarget($itemSource, $shareWith) {
		$shareFolder = \OCA\Files_Sharing\Helper::getShareFolder();
		$target = \OC\Files\Filesystem::normalizePath($shareFolder . '/' . basename($itemSource));

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

		return \OCA\Files_Sharing\Helper::generateUniqueTarget($target, $view);
	}

	public function formatItems($items, $format, $parameters = null) {
		if ($format === self::FORMAT_SHARED_STORAGE) {
			// Only 1 item should come through for this format call
			$item = array_shift($items);
			return [
				'parent' => $item['parent'],
				'path' => $item['path'],
				'storage' => $item['storage'],
				'permissions' => $item['permissions'],
				'uid_owner' => $item['uid_owner'],
			];
		} elseif ($format === self::FORMAT_GET_FOLDER_CONTENTS) {
			$files = [];
			foreach ($items as $item) {
				$file = [];
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
				$file['size'] = $item['size'];
				$files[] = $file;
			}
			return $files;
		} elseif ($format === self::FORMAT_OPENDIR) {
			$files = [];
			foreach ($items as $item) {
				$files[] = basename($item['file_target']);
			}
			return $files;
		} elseif ($format === self::FORMAT_GET_ALL) {
			$ids = [];
			foreach ($items as $item) {
				$ids[] = $item['file_source'];
			}
			return $ids;
		} elseif ($format === self::FORMAT_PERMISSIONS) {
			$filePermissions = [];
			foreach ($items as $item) {
				$filePermissions[$item['file_source']] = $item['permissions'];
			}
			return $filePermissions;
		} elseif ($format === self::FORMAT_TARGET_NAMES) {
			$targets = [];
			foreach ($items as $item) {
				$targets[] = $item['file_target'];
			}
			return $targets;
		}
		return [];
	}

	/**
	 * check if server2server share is enabled
	 *
	 * @param int $shareType
	 * @return boolean
	 */
	public function isShareTypeAllowed($shareType) {
		if ($shareType === IShare::TYPE_REMOTE) {
			return $this->federatedShareProvider->isOutgoingServer2serverShareEnabled();
		}

		if ($shareType === IShare::TYPE_REMOTE_GROUP) {
			return $this->federatedShareProvider->isOutgoingServer2serverGroupShareEnabled();
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
				$qb = \OC::$server->getDatabaseConnection()->getQueryBuilder();
				$qb->select('parent', 'uid_owner')
					->from('share')
					->where(
						$qb->expr()->eq('id', $qb->createNamedParameter($parent))
					);
				$result = $qb->execute();
				$item = $result->fetch();
				$result->closeCursor();
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
			\OCP\Server::get(LoggerInterface::class)->error('No owner found for reshare', ['app' => 'files_sharing']);
		}

		return $source;
	}

	/**
	 * @param string $target
	 * @param array $share
	 * @return array|false source item
	 */
	public static function getSource($target, $share) {
		if ($share['item_type'] === 'folder' && $target !== '') {
			// note: in case of ext storage mount points the path might be empty
			// which would cause a leading slash to appear
			$share['path'] = ltrim($share['path'] . '/' . $target, '/');
		}
		return self::resolveReshares($share);
	}
}
