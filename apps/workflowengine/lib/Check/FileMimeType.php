<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Check;

use OC\Files\Storage\Local;
use OCA\WorkflowEngine\Entity\File;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\IRequest;
use OCP\WorkflowEngine\IFileCheck;

class FileMimeType extends AbstractStringCheck implements IFileCheck {
	use TFileCheck {
		setFileInfo as _setFileInfo;
	}

	/** @var array */
	protected $mimeType;

	/**
	 * @param IL10N $l
	 * @param IRequest $request
	 * @param IMimeTypeDetector $mimeTypeDetector
	 */
	public function __construct(
		IL10N $l,
		protected IRequest $request,
		protected IMimeTypeDetector $mimeTypeDetector,
	) {
		parent::__construct($l);
	}

	/**
	 * @param IStorage $storage
	 * @param string $path
	 * @param bool $isDir
	 */
	public function setFileInfo(IStorage $storage, string $path, bool $isDir = false): void {
		$this->_setFileInfo($storage, $path, $isDir);
		if (!isset($this->mimeType[$this->storage->getId()][$this->path])
			|| $this->mimeType[$this->storage->getId()][$this->path] === '') {
			if ($isDir) {
				$this->mimeType[$this->storage->getId()][$this->path] = 'httpd/unix-directory';
			} else {
				$this->mimeType[$this->storage->getId()][$this->path] = null;
			}
		}
	}

	/**
	 * The mimetype is only cached if the file has a valid mimetype. Otherwise files access
	 * control will cache "application/octet-stream" for all the target node on:
	 * rename, move, copy and all other methods which create a new item
	 *
	 * To check this:
	 * 1. Add an automated tagging rule which tags on mimetype NOT "httpd/unix-directory"
	 * 2. Add an access control rule which checks for any mimetype
	 * 3. Create a folder and rename it, the folder should not be tagged, but it is
	 *
	 * @param string $storageId
	 * @param string|null $path
	 * @param string $mimeType
	 * @return string
	 */
	protected function cacheAndReturnMimeType(string $storageId, ?string $path, string $mimeType): string {
		if ($path !== null && $mimeType !== 'application/octet-stream') {
			$this->mimeType[$storageId][$path] = $mimeType;
		}

		return $mimeType;
	}

	/**
	 * Make sure that even though the content based check returns an application/octet-stream can still be checked based on mimetypemappings of their extension
	 *
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 */
	public function executeCheck($operator, $value) {
		return $this->executeStringCheck($operator, $value, $this->getActualValue());
	}

	/**
	 * @return string
	 */
	protected function getActualValue() {
		if ($this->mimeType[$this->storage->getId()][$this->path] !== null) {
			return $this->mimeType[$this->storage->getId()][$this->path];
		}
		$cacheEntry = $this->storage->getCache()->get($this->path);
		if ($cacheEntry && $cacheEntry->getMimeType() !== 'application/octet-stream') {
			return $this->cacheAndReturnMimeType($this->storage->getId(), $this->path, $cacheEntry->getMimeType());
		}

		if ($this->storage->file_exists($this->path) &&
			$this->storage->filesize($this->path) &&
			$this->storage->instanceOfStorage(Local::class)
		) {
			$path = $this->storage->getLocalFile($this->path);
			$mimeType = $this->mimeTypeDetector->detectContent($path);
			return $this->cacheAndReturnMimeType($this->storage->getId(), $this->path, $mimeType);
		}

		if ($this->isWebDAVRequest() || $this->isPublicWebDAVRequest()) {
			// Creating a folder
			if ($this->request->getMethod() === 'MKCOL') {
				return 'httpd/unix-directory';
			}
		}

		// We do not cache this, as the file did not exist yet.
		// In case it does in the future, we will check with detectContent()
		// again to get the real mimetype of the content, rather than
		// guessing it from the path.
		return $this->mimeTypeDetector->detectPath($this->path);
	}

	/**
	 * @return bool
	 */
	protected function isWebDAVRequest() {
		return substr($this->request->getScriptName(), 0 - strlen('/remote.php')) === '/remote.php' && (
			$this->request->getPathInfo() === '/webdav' ||
			str_starts_with($this->request->getPathInfo() ?? '', '/webdav/') ||
			$this->request->getPathInfo() === '/dav/files' ||
			str_starts_with($this->request->getPathInfo() ?? '', '/dav/files/') ||
			$this->request->getPathInfo() === '/dav/uploads' ||
			str_starts_with($this->request->getPathInfo() ?? '', '/dav/uploads/')
		);
	}

	/**
	 * @return bool
	 */
	protected function isPublicWebDAVRequest() {
		return substr($this->request->getScriptName(), 0 - strlen('/public.php')) === '/public.php' && (
			$this->request->getPathInfo() === '/webdav' ||
			str_starts_with($this->request->getPathInfo() ?? '', '/webdav/')
		);
	}

	public function supportedEntities(): array {
		return [ File::class ];
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
