<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Trashbin;

use Exception;
use OC\Files\Cache\Cache;
use OC\Files\Cache\CacheEntry;
use OC\Files\Cache\CacheQueryBuilder;
use OC\Files\Filesystem;
use OC\Files\Node\NonExistingFile;
use OC\Files\Node\NonExistingFolder;
use OC\Files\View;
use OC\User\NoUserException;
use OC_User;
use OCA\Files_Trashbin\AppInfo\Application;
use OCA\Files_Trashbin\Command\Expire;
use OCA\Files_Trashbin\Events\BeforeNodeRestoredEvent;
use OCA\Files_Trashbin\Events\NodeRestoredEvent;
use OCA\Files_Trashbin\Exceptions\CopyRecursiveException;
use OCA\Files_Versions\Storage;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Command\IBus;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Events\Node\BeforeNodeDeletedEvent;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\ILockingStorage;
use OCP\Files\Storage\IStorage;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Server;
use OCP\Util;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<BeforeNodeDeletedEvent> */
class Trashbin implements IEventListener {
	// unit: percentage; 50% of available disk space/quota
	public const DEFAULTMAXSIZE = 50;

	/**
	 * Ensure we don't need to scan the file during the move to trash
	 * by triggering the scan in the pre-hook
	 */
	public static function ensureFileScannedHook(Node $node): void {
		try {
			self::getUidAndFilename($node->getPath());
		} catch (NotFoundException $e) {
			// Nothing to scan for non existing files
		}
	}

	/**
	 * get the UID of the owner of the file and the path to the file relative to
	 * owners files folder
	 *
	 * @param string $filename
	 * @return array
	 * @throws NoUserException
	 */
	public static function getUidAndFilename($filename) {
		$uid = Filesystem::getOwner($filename);
		$userManager = Server::get(IUserManager::class);
		// if the user with the UID doesn't exists, e.g. because the UID points
		// to a remote user with a federated cloud ID we use the current logged-in
		// user. We need a valid local user to move the file to the right trash bin
		if (!$userManager->userExists($uid)) {
			$uid = OC_User::getUser();
		}
		if (!$uid) {
			// no owner, usually because of share link from ext storage
			return [null, null];
		}
		Filesystem::initMountPoints($uid);
		if ($uid !== OC_User::getUser()) {
			$info = Filesystem::getFileInfo($filename);
			$ownerView = new View('/' . $uid . '/files');
			try {
				$filename = $ownerView->getPath($info['fileid']);
			} catch (NotFoundException $e) {
				$filename = null;
			}
		}
		return [$uid, $filename];
	}

	/**
	 * get original location and deleted by of files for user
	 *
	 * @param string $user
	 * @return array<string, array<string, array{location: string, deletedBy: string}>>
	 */
	public static function getExtraData($user) {
		$query = Server::get(IDBConnection::class)->getQueryBuilder();
		$query->select('id', 'timestamp', 'location', 'deleted_by')
			->from('files_trash')
			->where($query->expr()->eq('user', $query->createNamedParameter($user)));
		$result = $query->executeQuery();
		$array = [];
		while ($row = $result->fetch()) {
			$array[$row['id']][$row['timestamp']] = [
				'location' => (string)$row['location'],
				'deletedBy' => (string)$row['deleted_by'],
			];
		}
		$result->closeCursor();
		return $array;
	}

	/**
	 * get original location of file
	 *
	 * @param string $user
	 * @param string $filename
	 * @param string $timestamp
	 * @return string|false original location
	 */
	public static function getLocation($user, $filename, $timestamp) {
		$query = Server::get(IDBConnection::class)->getQueryBuilder();
		$query->select('location')
			->from('files_trash')
			->where($query->expr()->eq('user', $query->createNamedParameter($user)))
			->andWhere($query->expr()->eq('id', $query->createNamedParameter($filename)))
			->andWhere($query->expr()->eq('timestamp', $query->createNamedParameter($timestamp)));

		$result = $query->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();

		if (isset($row['location'])) {
			return $row['location'];
		} else {
			return false;
		}
	}

	/** @param string $user */
	private static function setUpTrash($user): void {
		$view = new View('/' . $user);
		if (!$view->is_dir('files_trashbin')) {
			$view->mkdir('files_trashbin');
		}
		if (!$view->is_dir('files_trashbin/files')) {
			$view->mkdir('files_trashbin/files');
		}
		if (!$view->is_dir('files_trashbin/versions')) {
			$view->mkdir('files_trashbin/versions');
		}
		if (!$view->is_dir('files_trashbin/keys')) {
			$view->mkdir('files_trashbin/keys');
		}
	}


	/**
	 * copy file to owners trash
	 *
	 * @param string $sourcePath
	 * @param string $owner
	 * @param string $targetPath
	 * @param string $user
	 * @param int $timestamp
	 */
	private static function copyFilesToUser($sourcePath, $owner, $targetPath, $user, $timestamp): void {
		self::setUpTrash($owner);

		$targetFilename = basename($targetPath);
		$targetLocation = dirname($targetPath);

		$sourceFilename = basename($sourcePath);

		$view = new View('/');

		$target = $user . '/files_trashbin/files/' . static::getTrashFilename($targetFilename, $timestamp);
		$source = $owner . '/files_trashbin/files/' . static::getTrashFilename($sourceFilename, $timestamp);
		$free = $view->free_space($target);
		$isUnknownOrUnlimitedFreeSpace = $free < 0;
		$isEnoughFreeSpaceLeft = $view->filesize($source) < $free;
		if ($isUnknownOrUnlimitedFreeSpace || $isEnoughFreeSpaceLeft) {
			self::copy_recursive($source, $target, $view);
		}


		if ($view->file_exists($target)) {
			$query = Server::get(IDBConnection::class)->getQueryBuilder();
			$query->insert('files_trash')
				->setValue('id', $query->createNamedParameter($targetFilename))
				->setValue('timestamp', $query->createNamedParameter($timestamp))
				->setValue('location', $query->createNamedParameter($targetLocation))
				->setValue('user', $query->createNamedParameter($user))
				->setValue('deleted_by', $query->createNamedParameter($user));
			$result = $query->executeStatement();
			if (!$result) {
				Server::get(LoggerInterface::class)->error('trash bin database couldn\'t be updated for the files owner', ['app' => 'files_trashbin']);
			}
		}
	}


	/**
	 * move file to the trash bin
	 *
	 * @param string $file_path path to the deleted file/directory relative to the files root directory
	 * @param bool $ownerOnly delete for owner only (if file gets moved out of a shared folder)
	 *
	 * @return bool
	 */
	public static function move2trash($file_path, $ownerOnly = false) {
		// get the user for which the filesystem is setup
		$root = Filesystem::getRoot();
		[, $user] = explode('/', $root);
		[$owner, $ownerPath] = self::getUidAndFilename($file_path);

		// if no owner found (ex: ext storage + share link), will use the current user's trashbin then
		if (is_null($owner)) {
			$owner = $user;
			$ownerPath = $file_path;
		}

		$ownerView = new View('/' . $owner);

		// file has been deleted in between
		if (is_null($ownerPath) || $ownerPath === '') {
			return true;
		}

		$sourceInfo = $ownerView->getFileInfo('/files/' . $ownerPath);

		if ($sourceInfo === false) {
			return true;
		}

		self::setUpTrash($user);
		if ($owner !== $user) {
			// also setup for owner
			self::setUpTrash($owner);
		}

		$path_parts = pathinfo($ownerPath);

		$filename = $path_parts['basename'];
		$location = $path_parts['dirname'];
		/** @var ITimeFactory $timeFactory */
		$timeFactory = Server::get(ITimeFactory::class);
		$timestamp = $timeFactory->getTime();

		$lockingProvider = Server::get(ILockingProvider::class);

		// disable proxy to prevent recursive calls
		$trashPath = '/files_trashbin/files/' . static::getTrashFilename($filename, $timestamp);
		$gotLock = false;

		do {
			/** @var ILockingStorage & IStorage $trashStorage */
			[$trashStorage, $trashInternalPath] = $ownerView->resolvePath($trashPath);
			try {
				$trashStorage->acquireLock($trashInternalPath, ILockingProvider::LOCK_EXCLUSIVE, $lockingProvider);
				$gotLock = true;
			} catch (LockedException $e) {
				// a file with the same name is being deleted concurrently
				// nudge the timestamp a bit to resolve the conflict

				$timestamp = $timestamp + 1;

				$trashPath = '/files_trashbin/files/' . static::getTrashFilename($filename, $timestamp);
			}
		} while (!$gotLock);

		$sourceStorage = $sourceInfo->getStorage();
		$sourceInternalPath = $sourceInfo->getInternalPath();

		if ($trashStorage->file_exists($trashInternalPath)) {
			$trashStorage->unlink($trashInternalPath);
		}

		$configuredTrashbinSize = static::getConfiguredTrashbinSize($owner);
		if ($configuredTrashbinSize >= 0 && $sourceInfo->getSize() >= $configuredTrashbinSize) {
			return false;
		}

		try {
			$moveSuccessful = true;

			$trashStorage->moveFromStorage($sourceStorage, $sourceInternalPath, $trashInternalPath);
			if ($sourceStorage->getCache()->inCache($sourceInternalPath)) {
				$trashStorage->getUpdater()->renameFromStorage($sourceStorage, $sourceInternalPath, $trashInternalPath);
			}
		} catch (CopyRecursiveException $e) {
			$moveSuccessful = false;
			if ($trashStorage->file_exists($trashInternalPath)) {
				$trashStorage->unlink($trashInternalPath);
			}
			Server::get(LoggerInterface::class)->error('Couldn\'t move ' . $file_path . ' to the trash bin', ['app' => 'files_trashbin']);
		}

		if ($sourceStorage->file_exists($sourceInternalPath)) { // failed to delete the original file, abort
			if ($sourceStorage->is_dir($sourceInternalPath)) {
				$sourceStorage->rmdir($sourceInternalPath);
			} else {
				$sourceStorage->unlink($sourceInternalPath);
			}

			if ($sourceStorage->file_exists($sourceInternalPath)) {
				// undo the cache move
				$sourceStorage->getUpdater()->renameFromStorage($trashStorage, $trashInternalPath, $sourceInternalPath);
			} else {
				$trashStorage->getUpdater()->remove($trashInternalPath);
			}
			return false;
		}

		if ($moveSuccessful) {
			$query = Server::get(IDBConnection::class)->getQueryBuilder();
			$query->insert('files_trash')
				->setValue('id', $query->createNamedParameter($filename))
				->setValue('timestamp', $query->createNamedParameter($timestamp))
				->setValue('location', $query->createNamedParameter($location))
				->setValue('user', $query->createNamedParameter($owner))
				->setValue('deleted_by', $query->createNamedParameter($user));
			$result = $query->executeStatement();
			if (!$result) {
				Server::get(LoggerInterface::class)->error('trash bin database couldn\'t be updated', ['app' => 'files_trashbin']);
			}
			Util::emitHook('\OCA\Files_Trashbin\Trashbin', 'post_moveToTrash', ['filePath' => Filesystem::normalizePath($file_path),
				'trashPath' => Filesystem::normalizePath(static::getTrashFilename($filename, $timestamp))]);

			self::retainVersions($filename, $owner, $ownerPath, $timestamp);

			// if owner !== user we need to also add a copy to the users trash
			if ($user !== $owner && $ownerOnly === false) {
				self::copyFilesToUser($ownerPath, $owner, $file_path, $user, $timestamp);
			}
		}

		$trashStorage->releaseLock($trashInternalPath, ILockingProvider::LOCK_EXCLUSIVE, $lockingProvider);

		self::scheduleExpire($user);

		// if owner !== user we also need to update the owners trash size
		if ($owner !== $user) {
			self::scheduleExpire($owner);
		}

		return $moveSuccessful;
	}

	private static function getConfiguredTrashbinSize(string $user): int|float {
		$config = Server::get(IConfig::class);
		$userTrashbinSize = $config->getUserValue($user, 'files_trashbin', 'trashbin_size', '-1');
		if (is_numeric($userTrashbinSize) && ($userTrashbinSize > -1)) {
			return Util::numericToNumber($userTrashbinSize);
		}
		$systemTrashbinSize = $config->getAppValue('files_trashbin', 'trashbin_size', '-1');
		if (is_numeric($systemTrashbinSize)) {
			return Util::numericToNumber($systemTrashbinSize);
		}
		return -1;
	}

	/**
	 * Move file versions to trash so that they can be restored later
	 *
	 * @param string $filename of deleted file
	 * @param string $owner owner user id
	 * @param string $ownerPath path relative to the owner's home storage
	 * @param int $timestamp when the file was deleted
	 */
	private static function retainVersions($filename, $owner, $ownerPath, $timestamp) {
		if (Server::get(IAppManager::class)->isEnabledForUser('files_versions') && !empty($ownerPath)) {
			$user = OC_User::getUser();
			$rootView = new View('/');

			if ($rootView->is_dir($owner . '/files_versions/' . $ownerPath)) {
				if ($owner !== $user) {
					self::copy_recursive($owner . '/files_versions/' . $ownerPath, $owner . '/files_trashbin/versions/' . static::getTrashFilename(basename($ownerPath), $timestamp), $rootView);
				}
				self::move($rootView, $owner . '/files_versions/' . $ownerPath, $user . '/files_trashbin/versions/' . static::getTrashFilename($filename, $timestamp));
			} elseif ($versions = Storage::getVersions($owner, $ownerPath)) {
				foreach ($versions as $v) {
					if ($owner !== $user) {
						self::copy($rootView, $owner . '/files_versions' . $v['path'] . '.v' . $v['version'], $owner . '/files_trashbin/versions/' . static::getTrashFilename($v['name'] . '.v' . $v['version'], $timestamp));
					}
					self::move($rootView, $owner . '/files_versions' . $v['path'] . '.v' . $v['version'], $user . '/files_trashbin/versions/' . static::getTrashFilename($filename . '.v' . $v['version'], $timestamp));
				}
			}
		}
	}

	/**
	 * Move a file or folder on storage level
	 *
	 * @param View $view
	 * @param string $source
	 * @param string $target
	 * @return bool
	 */
	private static function move(View $view, $source, $target) {
		/** @var \OC\Files\Storage\Storage $sourceStorage */
		[$sourceStorage, $sourceInternalPath] = $view->resolvePath($source);
		/** @var \OC\Files\Storage\Storage $targetStorage */
		[$targetStorage, $targetInternalPath] = $view->resolvePath($target);
		/** @var \OC\Files\Storage\Storage $ownerTrashStorage */

		$result = $targetStorage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		if ($result) {
			$targetStorage->getUpdater()->renameFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		}
		return $result;
	}

	/**
	 * Copy a file or folder on storage level
	 *
	 * @param View $view
	 * @param string $source
	 * @param string $target
	 * @return bool
	 */
	private static function copy(View $view, $source, $target) {
		/** @var \OC\Files\Storage\Storage $sourceStorage */
		[$sourceStorage, $sourceInternalPath] = $view->resolvePath($source);
		/** @var \OC\Files\Storage\Storage $targetStorage */
		[$targetStorage, $targetInternalPath] = $view->resolvePath($target);
		/** @var \OC\Files\Storage\Storage $ownerTrashStorage */

		$result = $targetStorage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		if ($result) {
			$targetStorage->getUpdater()->update($targetInternalPath);
		}
		return $result;
	}

	/**
	 * Restore a file or folder from trash bin
	 *
	 * @param string $file path to the deleted file/folder relative to "files_trashbin/files/",
	 *                     including the timestamp suffix ".d12345678"
	 * @param string $filename name of the file/folder
	 * @param int $timestamp time when the file/folder was deleted
	 *
	 * @return bool true on success, false otherwise
	 */
	public static function restore($file, $filename, $timestamp) {
		$user = OC_User::getUser();
		$view = new View('/' . $user);

		$location = '';
		if ($timestamp) {
			$location = self::getLocation($user, $filename, $timestamp);
			if ($location === false) {
				Server::get(LoggerInterface::class)->error('trash bin database inconsistent! ($user: ' . $user . ' $filename: ' . $filename . ', $timestamp: ' . $timestamp . ')', ['app' => 'files_trashbin']);
			} else {
				// if location no longer exists, restore file in the root directory
				if ($location !== '/' &&
					(!$view->is_dir('files/' . $location) ||
						!$view->isCreatable('files/' . $location))
				) {
					$location = '';
				}
			}
		}

		// we need a  extension in case a file/dir with the same name already exists
		$uniqueFilename = self::getUniqueFilename($location, $filename, $view);

		$source = Filesystem::normalizePath('files_trashbin/files/' . $file);
		$target = Filesystem::normalizePath('files/' . $location . '/' . $uniqueFilename);
		if (!$view->file_exists($source)) {
			return false;
		}
		$mtime = $view->filemtime($source);

		// restore file
		if (!$view->isCreatable(dirname($target))) {
			throw new NotPermittedException("Can't restore trash item because the target folder is not writable");
		}

		$sourcePath = Filesystem::normalizePath($file);
		$targetPath = Filesystem::normalizePath('/' . $location . '/' . $uniqueFilename);

		$sourceNode = self::getNodeForPath($sourcePath);
		$targetNode = self::getNodeForPath($targetPath);
		$run = true;
		$event = new BeforeNodeRestoredEvent($sourceNode, $targetNode, $run);
		$dispatcher = Server::get(IEventDispatcher::class);
		$dispatcher->dispatchTyped($event);

		if (!$run) {
			return false;
		}

		$restoreResult = $view->rename($source, $target);

		// handle the restore result
		if ($restoreResult) {
			$fakeRoot = $view->getRoot();
			$view->chroot('/' . $user . '/files');
			$view->touch('/' . $location . '/' . $uniqueFilename, $mtime);
			$view->chroot($fakeRoot);
			Util::emitHook('\OCA\Files_Trashbin\Trashbin', 'post_restore', ['filePath' => $targetPath, 'trashPath' => $sourcePath]);

			$sourceNode = self::getNodeForPath($sourcePath);
			$targetNode = self::getNodeForPath($targetPath);
			$event = new NodeRestoredEvent($sourceNode, $targetNode);
			$dispatcher = Server::get(IEventDispatcher::class);
			$dispatcher->dispatchTyped($event);

			self::restoreVersions($view, $file, $filename, $uniqueFilename, $location, $timestamp);

			if ($timestamp) {
				$query = Server::get(IDBConnection::class)->getQueryBuilder();
				$query->delete('files_trash')
					->where($query->expr()->eq('user', $query->createNamedParameter($user)))
					->andWhere($query->expr()->eq('id', $query->createNamedParameter($filename)))
					->andWhere($query->expr()->eq('timestamp', $query->createNamedParameter($timestamp)));
				$query->executeStatement();
			}

			return true;
		}

		return false;
	}

	/**
	 * restore versions from trash bin
	 *
	 * @param View $view file view
	 * @param string $file complete path to file
	 * @param string $filename name of file once it was deleted
	 * @param string $uniqueFilename new file name to restore the file without overwriting existing files
	 * @param string $location location if file
	 * @param int $timestamp deletion time
	 * @return false|null
	 */
	private static function restoreVersions(View $view, $file, $filename, $uniqueFilename, $location, $timestamp) {
		if (Server::get(IAppManager::class)->isEnabledForUser('files_versions')) {
			$user = OC_User::getUser();
			$rootView = new View('/');

			$target = Filesystem::normalizePath('/' . $location . '/' . $uniqueFilename);

			[$owner, $ownerPath] = self::getUidAndFilename($target);

			// file has been deleted in between
			if (empty($ownerPath)) {
				return false;
			}

			if ($timestamp) {
				$versionedFile = $filename;
			} else {
				$versionedFile = $file;
			}

			if ($view->is_dir('/files_trashbin/versions/' . $file)) {
				$rootView->rename(Filesystem::normalizePath($user . '/files_trashbin/versions/' . $file), Filesystem::normalizePath($owner . '/files_versions/' . $ownerPath));
			} elseif ($versions = self::getVersionsFromTrash($versionedFile, $timestamp, $user)) {
				foreach ($versions as $v) {
					if ($timestamp) {
						$rootView->rename($user . '/files_trashbin/versions/' . static::getTrashFilename($versionedFile . '.v' . $v, $timestamp), $owner . '/files_versions/' . $ownerPath . '.v' . $v);
					} else {
						$rootView->rename($user . '/files_trashbin/versions/' . $versionedFile . '.v' . $v, $owner . '/files_versions/' . $ownerPath . '.v' . $v);
					}
				}
			}
		}
	}

	/**
	 * delete all files from the trash
	 */
	public static function deleteAll() {
		$user = OC_User::getUser();
		$userRoot = \OC::$server->getUserFolder($user)->getParent();
		$view = new View('/' . $user);
		$fileInfos = $view->getDirectoryContent('files_trashbin/files');

		try {
			$trash = $userRoot->get('files_trashbin');
		} catch (NotFoundException $e) {
			return false;
		}

		// Array to store the relative path in (after the file is deleted, the view won't be able to relativise the path anymore)
		$filePaths = [];
		foreach ($fileInfos as $fileInfo) {
			$filePaths[] = $view->getRelativePath($fileInfo->getPath());
		}
		unset($fileInfos); // save memory

		// Bulk PreDelete-Hook
		\OC_Hook::emit('\OCP\Trashbin', 'preDeleteAll', ['paths' => $filePaths]);

		// Single-File Hooks
		foreach ($filePaths as $path) {
			self::emitTrashbinPreDelete($path);
		}

		// actual file deletion
		$trash->delete();

		$query = Server::get(IDBConnection::class)->getQueryBuilder();
		$query->delete('files_trash')
			->where($query->expr()->eq('user', $query->createNamedParameter($user)));
		$query->executeStatement();

		// Bulk PostDelete-Hook
		\OC_Hook::emit('\OCP\Trashbin', 'deleteAll', ['paths' => $filePaths]);

		// Single-File Hooks
		foreach ($filePaths as $path) {
			self::emitTrashbinPostDelete($path);
		}

		$trash = $userRoot->newFolder('files_trashbin');
		$trash->newFolder('files');

		return true;
	}

	/**
	 * wrapper function to emit the 'preDelete' hook of \OCP\Trashbin before a file is deleted
	 *
	 * @param string $path
	 */
	protected static function emitTrashbinPreDelete($path) {
		\OC_Hook::emit('\OCP\Trashbin', 'preDelete', ['path' => $path]);
	}

	/**
	 * wrapper function to emit the 'delete' hook of \OCP\Trashbin after a file has been deleted
	 *
	 * @param string $path
	 */
	protected static function emitTrashbinPostDelete($path) {
		\OC_Hook::emit('\OCP\Trashbin', 'delete', ['path' => $path]);
	}

	/**
	 * delete file from trash bin permanently
	 *
	 * @param string $filename path to the file
	 * @param string $user
	 * @param int $timestamp of deletion time
	 *
	 * @return int|float size of deleted files
	 */
	public static function delete($filename, $user, $timestamp = null) {
		$userRoot = \OC::$server->getUserFolder($user)->getParent();
		$view = new View('/' . $user);
		$size = 0;

		if ($timestamp) {
			$query = Server::get(IDBConnection::class)->getQueryBuilder();
			$query->delete('files_trash')
				->where($query->expr()->eq('user', $query->createNamedParameter($user)))
				->andWhere($query->expr()->eq('id', $query->createNamedParameter($filename)))
				->andWhere($query->expr()->eq('timestamp', $query->createNamedParameter($timestamp)));
			$query->executeStatement();

			$file = static::getTrashFilename($filename, $timestamp);
		} else {
			$file = $filename;
		}

		$size += self::deleteVersions($view, $file, $filename, $timestamp, $user);

		try {
			$node = $userRoot->get('/files_trashbin/files/' . $file);
		} catch (NotFoundException $e) {
			return $size;
		}

		if ($node instanceof Folder) {
			$size += self::calculateSize(new View('/' . $user . '/files_trashbin/files/' . $file));
		} elseif ($node instanceof File) {
			$size += $view->filesize('/files_trashbin/files/' . $file);
		}

		self::emitTrashbinPreDelete('/files_trashbin/files/' . $file);
		$node->delete();
		self::emitTrashbinPostDelete('/files_trashbin/files/' . $file);

		return $size;
	}

	/**
	 * @param string $file
	 * @param string $filename
	 * @param ?int $timestamp
	 */
	private static function deleteVersions(View $view, $file, $filename, $timestamp, string $user): int|float {
		$size = 0;
		if (Server::get(IAppManager::class)->isEnabledForUser('files_versions')) {
			if ($view->is_dir('files_trashbin/versions/' . $file)) {
				$size += self::calculateSize(new View('/' . $user . '/files_trashbin/versions/' . $file));
				$view->unlink('files_trashbin/versions/' . $file);
			} elseif ($versions = self::getVersionsFromTrash($filename, $timestamp, $user)) {
				foreach ($versions as $v) {
					if ($timestamp) {
						$size += $view->filesize('/files_trashbin/versions/' . static::getTrashFilename($filename . '.v' . $v, $timestamp));
						$view->unlink('/files_trashbin/versions/' . static::getTrashFilename($filename . '.v' . $v, $timestamp));
					} else {
						$size += $view->filesize('/files_trashbin/versions/' . $filename . '.v' . $v);
						$view->unlink('/files_trashbin/versions/' . $filename . '.v' . $v);
					}
				}
			}
		}
		return $size;
	}

	/**
	 * check to see whether a file exists in trashbin
	 *
	 * @param string $filename path to the file
	 * @param int $timestamp of deletion time
	 * @return bool true if file exists, otherwise false
	 */
	public static function file_exists($filename, $timestamp = null) {
		$user = OC_User::getUser();
		$view = new View('/' . $user);

		if ($timestamp) {
			$filename = static::getTrashFilename($filename, $timestamp);
		}

		$target = Filesystem::normalizePath('files_trashbin/files/' . $filename);
		return $view->file_exists($target);
	}

	/**
	 * deletes used space for trash bin in db if user was deleted
	 *
	 * @param string $uid id of deleted user
	 * @return bool result of db delete operation
	 */
	public static function deleteUser($uid) {
		$query = Server::get(IDBConnection::class)->getQueryBuilder();
		$query->delete('files_trash')
			->where($query->expr()->eq('user', $query->createNamedParameter($uid)));
		return (bool)$query->executeStatement();
	}

	/**
	 * calculate remaining free space for trash bin
	 *
	 * @param int|float $trashbinSize current size of the trash bin
	 * @param string $user
	 * @return int|float available free space for trash bin
	 */
	private static function calculateFreeSpace(int|float $trashbinSize, string $user): int|float {
		$configuredTrashbinSize = static::getConfiguredTrashbinSize($user);
		if ($configuredTrashbinSize > -1) {
			return $configuredTrashbinSize - $trashbinSize;
		}

		$userObject = Server::get(IUserManager::class)->get($user);
		if (is_null($userObject)) {
			return 0;
		}
		$softQuota = true;
		$quota = $userObject->getQuota();
		if ($quota === null || $quota === 'none') {
			$quota = Filesystem::free_space('/');
			$softQuota = false;
			// inf or unknown free space
			if ($quota < 0) {
				$quota = PHP_INT_MAX;
			}
		} else {
			$quota = Util::computerFileSize($quota);
			// invalid quota
			if ($quota === false) {
				$quota = PHP_INT_MAX;
			}
		}

		// calculate available space for trash bin
		// subtract size of files and current trash bin size from quota
		if ($softQuota) {
			$userFolder = \OC::$server->getUserFolder($user);
			if (is_null($userFolder)) {
				return 0;
			}
			$free = $quota - $userFolder->getSize(false); // remaining free space for user
			if ($free > 0) {
				$availableSpace = ($free * self::DEFAULTMAXSIZE / 100) - $trashbinSize; // how much space can be used for versions
			} else {
				$availableSpace = $free - $trashbinSize;
			}
		} else {
			$availableSpace = $quota;
		}

		return Util::numericToNumber($availableSpace);
	}

	/**
	 * resize trash bin if necessary after a new file was added to Nextcloud
	 *
	 * @param string $user user id
	 */
	public static function resizeTrash($user) {
		$size = self::getTrashbinSize($user);

		$freeSpace = self::calculateFreeSpace($size, $user);

		if ($freeSpace < 0) {
			self::scheduleExpire($user);
		}
	}

	/**
	 * clean up the trash bin
	 *
	 * @param string $user
	 */
	public static function expire($user) {
		$trashBinSize = self::getTrashbinSize($user);
		$availableSpace = self::calculateFreeSpace($trashBinSize, $user);

		$dirContent = Helper::getTrashFiles('/', $user, 'mtime');

		// delete all files older then $retention_obligation
		[$delSize, $count] = self::deleteExpiredFiles($dirContent, $user);

		$availableSpace += $delSize;

		// delete files from trash until we meet the trash bin size limit again
		self::deleteFiles(array_slice($dirContent, $count), $user, $availableSpace);
	}

	/**
	 * @param string $user
	 */
	private static function scheduleExpire($user) {
		// let the admin disable auto expire
		/** @var Application $application */
		$application = Server::get(Application::class);
		$expiration = $application->getContainer()->query('Expiration');
		if ($expiration->isEnabled()) {
			Server::get(IBus::class)->push(new Expire($user));
		}
	}

	/**
	 * if the size limit for the trash bin is reached, we delete the oldest
	 * files in the trash bin until we meet the limit again
	 *
	 * @param array $files
	 * @param string $user
	 * @param int|float $availableSpace available disc space
	 * @return int|float size of deleted files
	 */
	protected static function deleteFiles(array $files, string $user, int|float $availableSpace): int|float {
		/** @var Application $application */
		$application = Server::get(Application::class);
		$expiration = $application->getContainer()->query('Expiration');
		$size = 0;

		if ($availableSpace < 0) {
			foreach ($files as $file) {
				if ($availableSpace < 0 && $expiration->isExpired($file['mtime'], true)) {
					$tmp = self::delete($file['name'], $user, $file['mtime']);
					Server::get(LoggerInterface::class)->info(
						'remove "' . $file['name'] . '" (' . $tmp . 'B) to meet the limit of trash bin size (50% of available quota) for user "{user}"',
						[
							'app' => 'files_trashbin',
							'user' => $user,
						]
					);
					$availableSpace += $tmp;
					$size += $tmp;
				} else {
					break;
				}
			}
		}
		return $size;
	}

	/**
	 * delete files older then max storage time
	 *
	 * @param array $files list of files sorted by mtime
	 * @param string $user
	 * @return array{int|float, int} size of deleted files and number of deleted files
	 */
	public static function deleteExpiredFiles($files, $user) {
		/** @var Expiration $expiration */
		$expiration = Server::get(Expiration::class);
		$size = 0;
		$count = 0;
		foreach ($files as $file) {
			$timestamp = $file['mtime'];
			$filename = $file['name'];
			if ($expiration->isExpired($timestamp)) {
				try {
					$size += self::delete($filename, $user, $timestamp);
					$count++;
				} catch (NotPermittedException $e) {
					Server::get(LoggerInterface::class)->warning('Removing "' . $filename . '" from trashbin failed for user "{user}"',
						[
							'exception' => $e,
							'app' => 'files_trashbin',
							'user' => $user,
						]
					);
				}
				Server::get(LoggerInterface::class)->info(
					'Remove "' . $filename . '" from trashbin for user "{user}" because it exceeds max retention obligation term.',
					[
						'app' => 'files_trashbin',
						'user' => $user,
					],
				);
			} else {
				break;
			}
		}

		return [$size, $count];
	}

	/**
	 * recursive copy to copy a whole directory
	 *
	 * @param string $source source path, relative to the users files directory
	 * @param string $destination destination path relative to the users root directory
	 * @param View $view file view for the users root directory
	 * @return int|float
	 * @throws Exceptions\CopyRecursiveException
	 */
	private static function copy_recursive($source, $destination, View $view): int|float {
		$size = 0;
		if ($view->is_dir($source)) {
			$view->mkdir($destination);
			$view->touch($destination, $view->filemtime($source));
			foreach ($view->getDirectoryContent($source) as $i) {
				$pathDir = $source . '/' . $i['name'];
				if ($view->is_dir($pathDir)) {
					$size += self::copy_recursive($pathDir, $destination . '/' . $i['name'], $view);
				} else {
					$size += $view->filesize($pathDir);
					$result = $view->copy($pathDir, $destination . '/' . $i['name']);
					if (!$result) {
						throw new CopyRecursiveException();
					}
					$view->touch($destination . '/' . $i['name'], $view->filemtime($pathDir));
				}
			}
		} else {
			$size += $view->filesize($source);
			$result = $view->copy($source, $destination);
			if (!$result) {
				throw new CopyRecursiveException();
			}
			$view->touch($destination, $view->filemtime($source));
		}
		return $size;
	}

	/**
	 * find all versions which belong to the file we want to restore
	 *
	 * @param string $filename name of the file which should be restored
	 * @param int $timestamp timestamp when the file was deleted
	 */
	private static function getVersionsFromTrash($filename, $timestamp, string $user): array {
		$view = new View('/' . $user . '/files_trashbin/versions');
		$versions = [];

		/** @var \OC\Files\Storage\Storage $storage */
		[$storage,] = $view->resolvePath('/');

		$pattern = Server::get(IDBConnection::class)->escapeLikeParameter(basename($filename));
		if ($timestamp) {
			// fetch for old versions
			$escapedTimestamp = Server::get(IDBConnection::class)->escapeLikeParameter((string)$timestamp);
			$pattern .= '.v%.d' . $escapedTimestamp;
			$offset = -strlen($escapedTimestamp) - 2;
		} else {
			$pattern .= '.v%';
		}

		// Manually fetch all versions from the file cache to be able to filter them by their parent
		$cache = $storage->getCache('');
		$query = new CacheQueryBuilder(
			Server::get(IDBConnection::class)->getQueryBuilder(),
			Server::get(IFilesMetadataManager::class),
		);
		$normalizedParentPath = ltrim(Filesystem::normalizePath(dirname('files_trashbin/versions/' . $filename)), '/');
		$parentId = $cache->getId($normalizedParentPath);
		if ($parentId === -1) {
			return [];
		}

		$query->selectFileCache()
			->whereStorageId($cache->getNumericStorageId())
			->andWhere($query->expr()->eq('parent', $query->createNamedParameter($parentId)))
			->andWhere($query->expr()->iLike('name', $query->createNamedParameter($pattern)));

		$result = $query->executeQuery();
		$entries = $result->fetchAll();
		$result->closeCursor();

		/** @var CacheEntry[] $matches */
		$matches = array_map(function (array $data) {
			return Cache::cacheEntryFromData($data, Server::get(IMimeTypeLoader::class));
		}, $entries);

		foreach ($matches as $ma) {
			if ($timestamp) {
				$parts = explode('.v', substr($ma['path'], 0, $offset));
				$versions[] = end($parts);
			} else {
				$parts = explode('.v', $ma['path']);
				$versions[] = end($parts);
			}
		}

		return $versions;
	}

	/**
	 * find unique extension for restored file if a file with the same name already exists
	 *
	 * @param string $location where the file should be restored
	 * @param string $filename name of the file
	 * @param View $view filesystem view relative to users root directory
	 * @return string with unique extension
	 */
	private static function getUniqueFilename($location, $filename, View $view) {
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		$name = pathinfo($filename, PATHINFO_FILENAME);
		$l = Util::getL10N('files_trashbin');

		$location = '/' . trim($location, '/');

		// if extension is not empty we set a dot in front of it
		if ($ext !== '') {
			$ext = '.' . $ext;
		}

		if ($view->file_exists('files' . $location . '/' . $filename)) {
			$i = 2;
			$uniqueName = $name . ' (' . $l->t('restored') . ')' . $ext;
			while ($view->file_exists('files' . $location . '/' . $uniqueName)) {
				$uniqueName = $name . ' (' . $l->t('restored') . ' ' . $i . ')' . $ext;
				$i++;
			}

			return $uniqueName;
		}

		return $filename;
	}

	/**
	 * get the size from a given root folder
	 *
	 * @param View $view file view on the root folder
	 * @return int|float size of the folder
	 */
	private static function calculateSize(View $view): int|float {
		$root = Server::get(IConfig::class)->getSystemValue('datadirectory', \OC::$SERVERROOT . '/data') . $view->getAbsolutePath('');
		if (!file_exists($root)) {
			return 0;
		}
		$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root), \RecursiveIteratorIterator::CHILD_FIRST);
		$size = 0;

		/**
		 * RecursiveDirectoryIterator on an NFS path isn't iterable with foreach
		 * This bug is fixed in PHP 5.5.9 or before
		 * See #8376
		 */
		$iterator->rewind();
		while ($iterator->valid()) {
			$path = $iterator->current();
			$relpath = substr($path, strlen($root) - 1);
			if (!$view->is_dir($relpath)) {
				$size += $view->filesize($relpath);
			}
			$iterator->next();
		}
		return $size;
	}

	/**
	 * get current size of trash bin from a given user
	 *
	 * @param string $user user who owns the trash bin
	 * @return int|float trash bin size
	 */
	private static function getTrashbinSize(string $user): int|float {
		$view = new View('/' . $user);
		$fileInfo = $view->getFileInfo('/files_trashbin');
		return isset($fileInfo['size']) ? $fileInfo['size'] : 0;
	}

	/**
	 * check if trash bin is empty for a given user
	 *
	 * @param string $user
	 * @return bool
	 */
	public static function isEmpty($user) {
		$view = new View('/' . $user . '/files_trashbin');
		if ($view->is_dir('/files') && $dh = $view->opendir('/files')) {
			while (($file = readdir($dh)) !== false) {
				if (!Filesystem::isIgnoredDir($file)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * @param $path
	 * @return string
	 */
	public static function preview_icon($path) {
		return Server::get(IURLGenerator::class)->linkToRoute('core_ajax_trashbin_preview', ['x' => 32, 'y' => 32, 'file' => $path]);
	}

	/**
	 * Return the filename used in the trash bin
	 */
	public static function getTrashFilename(string $filename, int $timestamp): string {
		$trashFilename = $filename . '.d' . $timestamp;
		$length = strlen($trashFilename);
		// oc_filecache `name` column has a limit of 250 chars
		$maxLength = 250;
		if ($length > $maxLength) {
			$trashFilename = substr_replace(
				$trashFilename,
				'',
				$maxLength / 2,
				$length - $maxLength
			);
		}
		return $trashFilename;
	}

	private static function getNodeForPath(string $path): Node {
		$user = OC_User::getUser();
		$rootFolder = Server::get(IRootFolder::class);

		if ($user !== false) {
			$userFolder = $rootFolder->getUserFolder($user);
			/** @var Folder */
			$trashFolder = $userFolder->getParent()->get('files_trashbin/files');
			try {
				return $trashFolder->get($path);
			} catch (NotFoundException $ex) {
			}
		}

		$view = Server::get(View::class);
		$fsView = Filesystem::getView();
		if ($fsView === null) {
			throw new Exception('View should not be null');
		}

		$fullPath = $fsView->getAbsolutePath($path);

		if (Filesystem::is_dir($path)) {
			return new NonExistingFolder($rootFolder, $view, $fullPath);
		} else {
			return new NonExistingFile($rootFolder, $view, $fullPath);
		}
	}

	public function handle(Event $event): void {
		if ($event instanceof BeforeNodeDeletedEvent) {
			self::ensureFileScannedHook($event->getNode());
		}
	}
}
