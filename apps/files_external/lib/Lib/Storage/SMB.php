<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Lib\Storage;

use Icewind\SMB\ACL;
use Icewind\SMB\BasicAuth;
use Icewind\SMB\Exception\AlreadyExistsException;
use Icewind\SMB\Exception\ConnectException;
use Icewind\SMB\Exception\Exception;
use Icewind\SMB\Exception\ForbiddenException;
use Icewind\SMB\Exception\InvalidArgumentException;
use Icewind\SMB\Exception\InvalidTypeException;
use Icewind\SMB\Exception\NotFoundException;
use Icewind\SMB\Exception\OutOfSpaceException;
use Icewind\SMB\Exception\TimedOutException;
use Icewind\SMB\IFileInfo;
use Icewind\SMB\Native\NativeServer;
use Icewind\SMB\Options;
use Icewind\SMB\ServerFactory;
use Icewind\SMB\Wrapped\Server;
use Icewind\Streams\CallbackWrapper;
use Icewind\Streams\IteratorDirectory;
use OC\Files\Filesystem;
use OC\Files\Storage\Common;
use OCA\Files_External\Lib\Notify\SMBNotifyHandler;
use OCP\Cache\CappedMemoryCache;
use OCP\Constants;
use OCP\Files\EntityTooLargeException;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\Notify\IChange;
use OCP\Files\Notify\IRenameChange;
use OCP\Files\NotPermittedException;
use OCP\Files\Storage\INotifyStorage;
use OCP\Files\StorageAuthException;
use OCP\Files\StorageNotAvailableException;
use OCP\ITempManager;
use Psr\Log\LoggerInterface;

class SMB extends Common implements INotifyStorage {
	/**
	 * @var \Icewind\SMB\IServer
	 */
	protected $server;

	/**
	 * @var \Icewind\SMB\IShare
	 */
	protected $share;

	/**
	 * @var string
	 */
	protected $root;

	/** @var CappedMemoryCache<IFileInfo> */
	protected CappedMemoryCache $statCache;

	/** @var LoggerInterface */
	protected $logger;

	/** @var bool */
	protected $showHidden;

	private bool $caseSensitive;

	/** @var bool */
	protected $checkAcl;

	public function __construct(array $parameters) {
		if (!isset($parameters['host'])) {
			throw new \Exception('Invalid configuration, no host provided');
		}

		if (isset($parameters['auth'])) {
			$auth = $parameters['auth'];
		} elseif (isset($parameters['user']) && isset($parameters['password']) && isset($parameters['share'])) {
			[$workgroup, $user] = $this->splitUser($parameters['user']);
			$auth = new BasicAuth($user, $workgroup, $parameters['password']);
		} else {
			throw new \Exception('Invalid configuration, no credentials provided');
		}

		if (isset($parameters['logger'])) {
			if (!$parameters['logger'] instanceof LoggerInterface) {
				throw new \Exception(
					'Invalid logger. Got '
					. get_class($parameters['logger'])
					. ' Expected ' . LoggerInterface::class
				);
			}
			$this->logger = $parameters['logger'];
		} else {
			$this->logger = \OCP\Server::get(LoggerInterface::class);
		}

		$options = new Options();
		if (isset($parameters['timeout'])) {
			$timeout = (int)$parameters['timeout'];
			if ($timeout > 0) {
				$options->setTimeout($timeout);
			}
		}
		$system = \OCP\Server::get(SystemBridge::class);
		$serverFactory = new ServerFactory($options, $system);
		$this->server = $serverFactory->createServer($parameters['host'], $auth);
		$this->share = $this->server->getShare(trim($parameters['share'], '/'));

		$this->root = $parameters['root'] ?? '/';
		$this->root = '/' . ltrim($this->root, '/');
		$this->root = rtrim($this->root, '/') . '/';

		$this->showHidden = isset($parameters['show_hidden']) && $parameters['show_hidden'];
		$this->caseSensitive = (bool)($parameters['case_sensitive'] ?? true);
		$this->checkAcl = isset($parameters['check_acl']) && $parameters['check_acl'];

		$this->statCache = new CappedMemoryCache();
		parent::__construct($parameters);
	}

	private function splitUser(string $user): array {
		if (str_contains($user, '/')) {
			return explode('/', $user, 2);
		} elseif (str_contains($user, '\\')) {
			return explode('\\', $user);
		}

		return [null, $user];
	}

	public function getId(): string {
		// FIXME: double slash to keep compatible with the old storage ids,
		// failure to do so will lead to creation of a new storage id and
		// loss of shares from the storage
		return 'smb::' . $this->server->getAuth()->getUsername() . '@' . $this->server->getHost() . '//' . $this->share->getName() . '/' . $this->root;
	}

	protected function buildPath(string $path): string {
		return Filesystem::normalizePath($this->root . '/' . $path, true, false, true);
	}

	protected function relativePath(string $fullPath): ?string {
		if ($fullPath === $this->root) {
			return '';
		} elseif (substr($fullPath, 0, strlen($this->root)) === $this->root) {
			return substr($fullPath, strlen($this->root));
		} else {
			return null;
		}
	}

	/**
	 * @throws StorageAuthException
	 * @throws \OCP\Files\NotFoundException
	 * @throws \OCP\Files\ForbiddenException
	 */
	protected function getFileInfo(string $path): IFileInfo {
		try {
			$path = $this->buildPath($path);
			$cached = $this->statCache[$path] ?? null;
			if ($cached instanceof IFileInfo) {
				return $cached;
			} else {
				$stat = $this->share->stat($path);
				$this->statCache[$path] = $stat;
				return $stat;
			}
		} catch (ConnectException $e) {
			$this->throwUnavailable($e);
		} catch (NotFoundException $e) {
			throw new \OCP\Files\NotFoundException($e->getMessage(), 0, $e);
		} catch (ForbiddenException $e) {
			// with php-smbclient, this exception is thrown when the provided password is invalid.
			// Possible is also ForbiddenException with a different error code, so we check it.
			if ($e->getCode() === 1) {
				$this->throwUnavailable($e);
			}
			throw new \OCP\Files\ForbiddenException($e->getMessage(), false, $e);
		}
	}

	/**
	 * @throws StorageAuthException
	 */
	protected function throwUnavailable(\Exception $e): never {
		$this->logger->error('Error while getting file info', ['exception' => $e]);
		throw new StorageAuthException($e->getMessage(), $e);
	}

	/**
	 * get the acl from fileinfo that is relevant for the configured user
	 */
	private function getACL(IFileInfo $file): ?ACL {
		try {
			$acls = $file->getAcls();
		} catch (Exception $e) {
			$this->logger->warning('Error while getting file acls', ['exception' => $e]);
			return null;
		}
		foreach ($acls as $user => $acl) {
			[, $user] = $this->splitUser($user); // strip domain
			if ($user === $this->server->getAuth()->getUsername()) {
				return $acl;
			}
		}

		return null;
	}

	/**
	 * @return \Generator<IFileInfo>
	 * @throws StorageNotAvailableException
	 */
	protected function getFolderContents(string $path): iterable {
		try {
			$path = ltrim($this->buildPath($path), '/');
			try {
				$files = $this->share->dir($path);
			} catch (ForbiddenException $e) {
				$this->logger->critical($e->getMessage(), ['exception' => $e]);
				throw new NotPermittedException();
			} catch (InvalidTypeException $e) {
				return;
			}
			foreach ($files as $file) {
				$this->statCache[$path . '/' . $file->getName()] = $file;
			}

			foreach ($files as $file) {
				try {
					// the isHidden check is done before checking the config boolean to ensure that the metadata is always fetch
					// so we trigger the below exceptions where applicable
					$hide = $file->isHidden() && !$this->showHidden;

					if ($this->checkAcl && $acl = $this->getACL($file)) {
						// if there is no explicit deny, we assume it's allowed
						// this doesn't take inheritance fully into account but if read permissions is denied for a parent we wouldn't be in this folder
						// additionally, it's better to have false negatives here then false positives
						if ($acl->denies(ACL::MASK_READ) || $acl->denies(ACL::MASK_EXECUTE)) {
							$this->logger->debug('Hiding non readable entry ' . $file->getName());
							continue;
						}
					}

					if ($hide) {
						$this->logger->debug('hiding hidden file ' . $file->getName());
					}
					if (!$hide) {
						yield $file;
					}
				} catch (ForbiddenException $e) {
					$this->logger->debug($e->getMessage(), ['exception' => $e]);
				} catch (NotFoundException $e) {
					$this->logger->debug('Hiding forbidden entry ' . $file->getName(), ['exception' => $e]);
				}
			}
		} catch (ConnectException $e) {
			$this->logger->error('Error while getting folder content', ['exception' => $e]);
			throw new StorageNotAvailableException($e->getMessage(), (int)$e->getCode(), $e);
		} catch (NotFoundException $e) {
			throw new \OCP\Files\NotFoundException($e->getMessage(), 0, $e);
		}
	}

	protected function formatInfo(IFileInfo $info): array {
		$result = [
			'size' => $info->getSize(),
			'mtime' => $info->getMTime(),
		];
		if ($info->isDirectory()) {
			$result['type'] = 'dir';
		} else {
			$result['type'] = 'file';
		}
		return $result;
	}

	/**
	 * Rename the files. If the source or the target is the root, the rename won't happen.
	 *
	 * @param string $source the old name of the path
	 * @param string $target the new name of the path
	 */
	public function rename(string $source, string $target, bool $retry = true): bool {
		if ($this->isRootDir($source) || $this->isRootDir($target)) {
			return false;
		}
		if ($this->caseSensitive === false
			&& mb_strtolower($target) === mb_strtolower($source)
		) {
			// Forbid changing case only on case-insensitive file system
			return false;
		}

		$absoluteSource = $this->buildPath($source);
		$absoluteTarget = $this->buildPath($target);
		try {
			$result = $this->share->rename($absoluteSource, $absoluteTarget);
		} catch (AlreadyExistsException $e) {
			if ($retry) {
				$this->remove($target);
				$result = $this->share->rename($absoluteSource, $absoluteTarget);
			} else {
				$this->logger->warning($e->getMessage(), ['exception' => $e]);
				return false;
			}
		} catch (InvalidArgumentException $e) {
			if ($retry) {
				$this->remove($target);
				$result = $this->share->rename($absoluteSource, $absoluteTarget);
			} else {
				$this->logger->warning($e->getMessage(), ['exception' => $e]);
				return false;
			}
		} catch (\Exception $e) {
			$this->logger->warning($e->getMessage(), ['exception' => $e]);
			return false;
		}
		unset($this->statCache[$absoluteSource], $this->statCache[$absoluteTarget]);
		return $result;
	}

	public function stat(string $path, bool $retry = true): array|false {
		try {
			$result = $this->formatInfo($this->getFileInfo($path));
		} catch (\OCP\Files\ForbiddenException $e) {
			return false;
		} catch (\OCP\Files\NotFoundException $e) {
			return false;
		} catch (TimedOutException $e) {
			if ($retry) {
				return $this->stat($path, false);
			} else {
				throw $e;
			}
		}
		if ($this->remoteIsShare() && $this->isRootDir($path)) {
			$result['mtime'] = $this->shareMTime();
		}
		return $result;
	}

	/**
	 * get the best guess for the modification time of the share
	 */
	private function shareMTime(): int {
		$highestMTime = 0;
		$files = $this->share->dir($this->root);
		foreach ($files as $fileInfo) {
			try {
				if ($fileInfo->getMTime() > $highestMTime) {
					$highestMTime = $fileInfo->getMTime();
				}
			} catch (NotFoundException $e) {
				// Ignore this, can happen on unavailable DFS shares
			} catch (ForbiddenException $e) {
				// Ignore this too - it's a symlink
			}
		}
		return $highestMTime;
	}

	/**
	 * Check if the path is our root dir (not the smb one)
	 */
	private function isRootDir(string $path): bool {
		return $path === '' || $path === '/' || $path === '.';
	}

	/**
	 * Check if our root points to a smb share
	 */
	private function remoteIsShare(): bool {
		return $this->share->getName() && (!$this->root || $this->root === '/');
	}

	public function unlink(string $path): bool {
		if ($this->isRootDir($path)) {
			return false;
		}

		try {
			if ($this->is_dir($path)) {
				return $this->rmdir($path);
			} else {
				$path = $this->buildPath($path);
				unset($this->statCache[$path]);
				$this->share->del($path);
				return true;
			}
		} catch (NotFoundException $e) {
			return false;
		} catch (ForbiddenException $e) {
			return false;
		} catch (ConnectException $e) {
			$this->logger->error('Error while deleting file', ['exception' => $e]);
			throw new StorageNotAvailableException($e->getMessage(), (int)$e->getCode(), $e);
		}
	}

	/**
	 * check if a file or folder has been updated since $time
	 */
	public function hasUpdated(string $path, int $time): bool {
		if (!$path and $this->root === '/') {
			// mtime doesn't work for shares, but giving the nature of the backend,
			// doing a full update is still just fast enough
			return true;
		} else {
			$actualTime = $this->filemtime($path);
			return $actualTime > $time || $actualTime === 0;
		}
	}

	/**
	 * @return resource|false
	 */
	public function fopen(string $path, string $mode) {
		$fullPath = $this->buildPath($path);
		try {
			switch ($mode) {
				case 'r':
				case 'rb':
					if (!$this->file_exists($path)) {
						$this->logger->warning('Failed to open ' . $path . ' on ' . $this->getId() . ', file doesn\'t exist.');
						return false;
					}
					return $this->share->read($fullPath);
				case 'w':
				case 'wb':
					$source = $this->share->write($fullPath);
					return CallBackWrapper::wrap($source, null, null, function () use ($fullPath): void {
						unset($this->statCache[$fullPath]);
					});
				case 'a':
				case 'ab':
				case 'r+':
				case 'w+':
				case 'wb+':
				case 'a+':
				case 'x':
				case 'x+':
				case 'c':
				case 'c+':
					//emulate these
					if (strrpos($path, '.') !== false) {
						$ext = substr($path, strrpos($path, '.'));
					} else {
						$ext = '';
					}
					if ($this->file_exists($path)) {
						if (!$this->isUpdatable($path)) {
							$this->logger->warning('Failed to open ' . $path . ' on ' . $this->getId() . ', file not updatable.');
							return false;
						}
						$tmpFile = $this->getCachedFile($path);
					} else {
						if (!$this->isCreatable(dirname($path))) {
							$this->logger->warning('Failed to open ' . $path . ' on ' . $this->getId() . ', parent directory not writable.');
							return false;
						}
						$tmpFile = \OCP\Server::get(ITempManager::class)->getTemporaryFile($ext);
					}
					$source = fopen($tmpFile, $mode);
					$share = $this->share;
					return CallbackWrapper::wrap($source, null, null, function () use ($tmpFile, $fullPath, $share): void {
						unset($this->statCache[$fullPath]);
						$share->put($tmpFile, $fullPath);
						unlink($tmpFile);
					});
			}
			return false;
		} catch (NotFoundException $e) {
			$this->logger->warning('Failed to open ' . $path . ' on ' . $this->getId() . ', not found.', ['exception' => $e]);
			return false;
		} catch (ForbiddenException $e) {
			$this->logger->warning('Failed to open ' . $path . ' on ' . $this->getId() . ', forbidden.', ['exception' => $e]);
			return false;
		} catch (OutOfSpaceException $e) {
			$this->logger->warning('Failed to open ' . $path . ' on ' . $this->getId() . ', out of space.', ['exception' => $e]);
			throw new EntityTooLargeException('not enough available space to create file', 0, $e);
		} catch (ConnectException $e) {
			$this->logger->error('Error while opening file ' . $path . ' on ' . $this->getId(), ['exception' => $e]);
			throw new StorageNotAvailableException($e->getMessage(), (int)$e->getCode(), $e);
		}
	}

	public function rmdir(string $path): bool {
		if ($this->isRootDir($path)) {
			return false;
		}

		try {
			$this->statCache = new CappedMemoryCache();
			$content = $this->share->dir($this->buildPath($path));
			foreach ($content as $file) {
				if ($file->isDirectory()) {
					$this->rmdir($path . '/' . $file->getName());
				} else {
					$this->share->del($file->getPath());
				}
			}
			$this->share->rmdir($this->buildPath($path));
			return true;
		} catch (NotFoundException $e) {
			return false;
		} catch (ForbiddenException $e) {
			return false;
		} catch (ConnectException $e) {
			$this->logger->error('Error while removing folder', ['exception' => $e]);
			throw new StorageNotAvailableException($e->getMessage(), (int)$e->getCode(), $e);
		}
	}

	public function touch(string $path, ?int $mtime = null): bool {
		try {
			if (!$this->file_exists($path)) {
				$fh = $this->share->write($this->buildPath($path));
				fclose($fh);
				return true;
			}
			return false;
		} catch (OutOfSpaceException $e) {
			throw new EntityTooLargeException('not enough available space to create file', 0, $e);
		} catch (ConnectException $e) {
			$this->logger->error('Error while creating file', ['exception' => $e]);
			throw new StorageNotAvailableException($e->getMessage(), (int)$e->getCode(), $e);
		}
	}

	public function getMetaData(string $path): ?array {
		try {
			$fileInfo = $this->getFileInfo($path);
		} catch (\OCP\Files\NotFoundException $e) {
			return null;
		} catch (\OCP\Files\ForbiddenException $e) {
			return null;
		}

		return $this->getMetaDataFromFileInfo($fileInfo);
	}

	private function getMetaDataFromFileInfo(IFileInfo $fileInfo): array {
		$permissions = Constants::PERMISSION_READ + Constants::PERMISSION_SHARE;

		if (
			!$fileInfo->isReadOnly() || $fileInfo->isDirectory()
		) {
			$permissions += Constants::PERMISSION_DELETE;
			$permissions += Constants::PERMISSION_UPDATE;
			if ($fileInfo->isDirectory()) {
				$permissions += Constants::PERMISSION_CREATE;
			}
		}

		$data = [];
		if ($fileInfo->isDirectory()) {
			$data['mimetype'] = 'httpd/unix-directory';
		} else {
			$data['mimetype'] = \OCP\Server::get(IMimeTypeDetector::class)->detectPath($fileInfo->getPath());
		}
		$data['mtime'] = $fileInfo->getMTime();
		if ($fileInfo->isDirectory()) {
			$data['size'] = -1; //unknown
		} else {
			$data['size'] = $fileInfo->getSize();
		}
		$data['etag'] = $this->getETag($fileInfo->getPath());
		$data['storage_mtime'] = $data['mtime'];
		$data['permissions'] = $permissions;
		$data['name'] = $fileInfo->getName();

		return $data;
	}

	public function opendir(string $path) {
		try {
			$files = $this->getFolderContents($path);
		} catch (NotFoundException $e) {
			return false;
		} catch (NotPermittedException $e) {
			return false;
		}
		$names = array_map(function ($info) {
			/** @var IFileInfo $info */
			return $info->getName();
		}, iterator_to_array($files));
		return IteratorDirectory::wrap($names);
	}

	public function getDirectoryContent(string $directory): \Traversable {
		try {
			$files = $this->getFolderContents($directory);
			foreach ($files as $file) {
				yield $this->getMetaDataFromFileInfo($file);
			}
		} catch (NotFoundException $e) {
			return;
		} catch (NotPermittedException $e) {
			return;
		}
	}

	public function filetype(string $path): string|false {
		try {
			return $this->getFileInfo($path)->isDirectory() ? 'dir' : 'file';
		} catch (\OCP\Files\NotFoundException $e) {
			return false;
		} catch (\OCP\Files\ForbiddenException $e) {
			return false;
		}
	}

	public function mkdir(string $path): bool {
		$path = $this->buildPath($path);
		try {
			$this->share->mkdir($path);
			return true;
		} catch (ConnectException $e) {
			$this->logger->error('Error while creating folder', ['exception' => $e]);
			throw new StorageNotAvailableException($e->getMessage(), (int)$e->getCode(), $e);
		} catch (Exception $e) {
			return false;
		}
	}

	public function file_exists(string $path): bool {
		try {
			// Case sensitive filesystem doesn't matter for root directory
			if ($this->caseSensitive === false && $path !== '') {
				$filename = basename($path);
				$siblings = $this->getDirectoryContent(dirname($path));
				foreach ($siblings as $sibling) {
					if ($sibling['name'] === $filename) {
						return true;
					}
				}
				return false;
			}
			$this->getFileInfo($path);
			return true;
		} catch (\OCP\Files\NotFoundException $e) {
			return false;
		} catch (\OCP\Files\ForbiddenException $e) {
			return false;
		} catch (ConnectException $e) {
			throw new StorageNotAvailableException($e->getMessage(), (int)$e->getCode(), $e);
		}
	}

	public function isReadable(string $path): bool {
		try {
			$info = $this->getFileInfo($path);
			return $this->showHidden || !$info->isHidden();
		} catch (\OCP\Files\NotFoundException $e) {
			return false;
		} catch (\OCP\Files\ForbiddenException $e) {
			return false;
		}
	}

	public function isUpdatable(string $path): bool {
		try {
			$info = $this->getFileInfo($path);
			// following windows behaviour for read-only folders: they can be written into
			// (https://support.microsoft.com/en-us/kb/326549 - "cause" section)
			return ($this->showHidden || !$info->isHidden()) && (!$info->isReadOnly() || $info->isDirectory());
		} catch (\OCP\Files\NotFoundException $e) {
			return false;
		} catch (\OCP\Files\ForbiddenException $e) {
			return false;
		}
	}

	public function isDeletable(string $path): bool {
		try {
			$info = $this->getFileInfo($path);
			return ($this->showHidden || !$info->isHidden()) && !$info->isReadOnly();
		} catch (\OCP\Files\NotFoundException $e) {
			return false;
		} catch (\OCP\Files\ForbiddenException $e) {
			return false;
		}
	}

	/**
	 * check if smbclient is installed
	 */
	public static function checkDependencies(): array|bool {
		$system = \OCP\Server::get(SystemBridge::class);
		return Server::available($system) || NativeServer::available($system) ?: ['smbclient'];
	}

	public function test(): bool {
		try {
			return parent::test();
		} catch (StorageAuthException $e) {
			return false;
		} catch (ForbiddenException $e) {
			return false;
		} catch (Exception $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return false;
		}
	}

	public function listen(string $path, callable $callback): void {
		$this->notify($path)->listen(function (IChange $change) use ($callback) {
			if ($change instanceof IRenameChange) {
				return $callback($change->getType(), $change->getPath(), $change->getTargetPath());
			} else {
				return $callback($change->getType(), $change->getPath());
			}
		});
	}

	public function notify(string $path): SMBNotifyHandler {
		$path = '/' . ltrim($path, '/');
		$shareNotifyHandler = $this->share->notify($this->buildPath($path));
		return new SMBNotifyHandler($shareNotifyHandler, $this->root);
	}
}
