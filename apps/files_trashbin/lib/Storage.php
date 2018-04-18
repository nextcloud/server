<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Trashbin;

use OC\Files\Filesystem;
use OC\Files\Storage\Wrapper\Wrapper;
use OC\Files\View;
use OCA\Files_Trashbin\Events\MoveToTrashEvent;
use OCP\Encryption\Exceptions\GenericEncryptionException;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\ILogger;
use OCP\IUserManager;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Storage extends Wrapper {

	private $mountPoint;
	// remember already deleted files to avoid infinite loops if the trash bin
	// move files across storages
	private $deletedFiles = array();

	/**
	 * Disable trash logic
	 *
	 * @var bool
	 */
	private static $disableTrash = false;

	/**
	 * remember which file/folder was moved out of s shared folder
	 * in this case we want to add a copy to the owners trash bin
	 *
	 * @var array
	 */
	private static $moveOutOfSharedFolder = [];

	/** @var  IUserManager */
	private $userManager;

	/** @var ILogger */
	private $logger;

	/** @var EventDispatcher */
	private $eventDispatcher;

	/** @var IRootFolder */
	private $rootFolder;

	/**
	 * Storage constructor.
	 *
	 * @param array $parameters
	 * @param IUserManager|null $userManager
	 * @param ILogger|null $logger
	 * @param EventDispatcher|null $eventDispatcher
	 * @param IRootFolder|null $rootFolder
	 */
	public function __construct($parameters,
								IUserManager $userManager = null,
								ILogger $logger = null,
								EventDispatcher $eventDispatcher = null,
								IRootFolder $rootFolder = null) {
		$this->mountPoint = $parameters['mountPoint'];
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->eventDispatcher = $eventDispatcher;
		$this->rootFolder = $rootFolder;
		parent::__construct($parameters);
	}

	/**
	 * @internal
	 */
	public static function preRenameHook($params) {
		// in cross-storage cases, a rename is a copy + unlink,
		// that last unlink must not go to trash, only exception:
		// if the file was moved from a shared storage to a local folder,
		// in this case the owner should get a copy in his trash bin so that
		// they can restore the files again

		$oldPath = $params['oldpath'];
		$newPath = dirname($params['newpath']);
		$currentUser = \OC::$server->getUserSession()->getUser();

		$fileMovedOutOfSharedFolder = false;

		try {
			if ($currentUser) {
				$currentUserId = $currentUser->getUID();

				$view = new View($currentUserId . '/files');
				$fileInfo = $view->getFileInfo($oldPath);
				if ($fileInfo) {
					$sourceStorage = $fileInfo->getStorage();
					$sourceOwner = $view->getOwner($oldPath);
					$targetOwner = $view->getOwner($newPath);

					if ($sourceOwner !== $targetOwner
						&& $sourceStorage->instanceOfStorage('OCA\Files_Sharing\SharedStorage')
					) {
						$fileMovedOutOfSharedFolder = true;
					}
				}
			}
		} catch (\Exception $e) {
			// do nothing, in this case we just disable the trashbin and continue
			\OC::$server->getLogger()->logException($e, [
				'message' => 'Trashbin storage could not check if a file was moved out of a shared folder.',
				'level' => \OCP\Util::DEBUG,
				'app' => 'files_trashbin',
			]);
		}

		if($fileMovedOutOfSharedFolder) {
			self::$moveOutOfSharedFolder['/' . $currentUserId . '/files' . $oldPath] = true;
		} else {
			self::$disableTrash = true;
		}

	}

	/**
	 * @internal
	 */
	public static function postRenameHook($params) {
		self::$disableTrash = false;
	}

	/**
	 * Rename path1 to path2 by calling the wrapped storage.
	 *
	 * @param string $path1 first path
	 * @param string $path2 second path
	 * @return bool
	 */
	public function rename($path1, $path2) {
		$result = $this->storage->rename($path1, $path2);
		if ($result === false) {
			// when rename failed, the post_rename hook isn't triggered,
			// but we still want to reenable the trash logic
			self::$disableTrash = false;
		}
		return $result;
	}

	/**
	 * Deletes the given file by moving it into the trashbin.
	 *
	 * @param string $path path of file or folder to delete
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	public function unlink($path) {
		try {
			if (isset(self::$moveOutOfSharedFolder[$this->mountPoint . $path])) {
				$result = $this->doDelete($path, 'unlink', true);
				unset(self::$moveOutOfSharedFolder[$this->mountPoint . $path]);
			} else {
				$result = $this->doDelete($path, 'unlink');
			}
		} catch (GenericEncryptionException $e) {
			// in case of a encryption exception we delete the file right away
			$this->logger->info(
				"Can't move file" .  $path .
				"to the trash bin, therefore it was deleted right away");

			$result = $this->storage->unlink($path);
		}

		return $result;
	}

	/**
	 * Deletes the given folder by moving it into the trashbin.
	 *
	 * @param string $path path of folder to delete
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	public function rmdir($path) {
		if (isset(self::$moveOutOfSharedFolder[$this->mountPoint . $path])) {
			$result = $this->doDelete($path, 'rmdir', true);
			unset(self::$moveOutOfSharedFolder[$this->mountPoint . $path]);
		} else {
			$result = $this->doDelete($path, 'rmdir');
		}

		return $result;
	}

	/**
	 * check if it is a file located in data/user/files only files in the
	 * 'files' directory should be moved to the trash
	 *
	 * @param $path
	 * @return bool
	 */
	protected function shouldMoveToTrash($path){

		// check if there is a app which want to disable the trash bin for this file
		$fileId = $this->storage->getCache()->getId($path);
		$nodes = $this->rootFolder->getById($fileId);
		foreach ($nodes as $node) {
			$event = $this->createMoveToTrashEvent($node);
			$this->eventDispatcher->dispatch('OCA\Files_Trashbin::moveToTrash', $event);
			if ($event->shouldMoveToTrashBin() === false) {
				return false;
			}
		}

		$normalized = Filesystem::normalizePath($this->mountPoint . '/' . $path);
		$parts = explode('/', $normalized);
		if (count($parts) < 4) {
			return false;
		}

		if ($parts[2] === 'files' && $this->userManager->userExists($parts[1])) {
			return true;
		}

		return false;
	}

	/**
	 * get move to trash event
	 *
	 * @param Node $node
	 * @return MoveToTrashEvent
	 */
	protected function createMoveToTrashEvent(Node $node) {
		return new MoveToTrashEvent($node);
	}

	/**
	 * Run the delete operation with the given method
	 *
	 * @param string $path path of file or folder to delete
	 * @param string $method either "unlink" or "rmdir"
	 * @param bool $ownerOnly delete for owner only (if file gets moved out of a shared folder)
	 *
	 * @return bool true if the operation succeeded, false otherwise
	 */
	private function doDelete($path, $method, $ownerOnly = false) {
		if (self::$disableTrash
			|| !\OC::$server->getAppManager()->isEnabledForUser('files_trashbin')
			|| (pathinfo($path, PATHINFO_EXTENSION) === 'part')
			|| $this->shouldMoveToTrash($path) === false
		) {
			return call_user_func_array([$this->storage, $method], [$path]);
		}

		// check permissions before we continue, this is especially important for
		// shared files
		if (!$this->isDeletable($path)) {
			return false;
		}

		$normalized = Filesystem::normalizePath($this->mountPoint . '/' . $path, true, false, true);
		$result = true;
		$view = Filesystem::getView();
		if (!isset($this->deletedFiles[$normalized]) && $view instanceof View) {
			$this->deletedFiles[$normalized] = $normalized;
			if ($filesPath = $view->getRelativePath($normalized)) {
				$filesPath = trim($filesPath, '/');
				$result = \OCA\Files_Trashbin\Trashbin::move2trash($filesPath, $ownerOnly);
				// in cross-storage cases the file will be copied
				// but not deleted, so we delete it here
				if ($result) {
					call_user_func_array([$this->storage, $method], [$path]);
				}
			} else {
				$result = call_user_func_array([$this->storage, $method], [$path]);
			}
			unset($this->deletedFiles[$normalized]);
		} else if ($this->storage->file_exists($path)) {
			$result = call_user_func_array([$this->storage, $method], [$path]);
		}

		return $result;
	}

	/**
	 * Setup the storate wrapper callback
	 */
	public static function setupStorage() {
		\OC\Files\Filesystem::addStorageWrapper('oc_trashbin', function ($mountPoint, $storage) {
			return new \OCA\Files_Trashbin\Storage(
				array('storage' => $storage, 'mountPoint' => $mountPoint),
				\OC::$server->getUserManager(),
				\OC::$server->getLogger(),
				\OC::$server->getEventDispatcher(),
				\OC::$server->getLazyRootFolder()
			);
		}, 1);
	}

}
