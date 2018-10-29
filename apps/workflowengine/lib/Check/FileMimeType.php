<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\WorkflowEngine\Check;


use OCP\Files\IMimeTypeDetector;
use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\IRequest;

class FileMimeType extends AbstractStringCheck {

	/** @var array */
	protected $mimeType;

	/** @var IRequest */
	protected $request;

	/** @var IMimeTypeDetector */
	protected $mimeTypeDetector;

	/** @var IStorage */
	protected $storage;

	/** @var string */
	protected $path;

	/**
	 * @param IL10N $l
	 * @param IRequest $request
	 * @param IMimeTypeDetector $mimeTypeDetector
	 */
	public function __construct(IL10N $l, IRequest $request, IMimeTypeDetector $mimeTypeDetector) {
		parent::__construct($l);
		$this->request = $request;
		$this->mimeTypeDetector = $mimeTypeDetector;
	}

	/**
	 * @param IStorage $storage
	 * @param string $path
	 */
	public function setFileInfo(IStorage $storage, $path) {
		$this->storage = $storage;
		$this->path = $path;
		if (!isset($this->mimeType[$this->storage->getId()][$this->path])
			|| $this->mimeType[$this->storage->getId()][$this->path] === '') {
			$this->mimeType[$this->storage->getId()][$this->path] = null;
		}
	}

	/**
	 * @return string
	 */
	protected function getActualValue() {
		if ($this->mimeType[$this->storage->getId()][$this->path] !== null) {
			return $this->mimeType[$this->storage->getId()][$this->path];
		}

		if ($this->storage->is_dir($this->path)) {
			$this->mimeType[$this->storage->getId()][$this->path] = 'httpd/unix-directory';
			return $this->mimeType[$this->storage->getId()][$this->path];
		}

		if ($this->isWebDAVRequest()) {
			// Creating a folder
			if ($this->request->getMethod() === 'MKCOL') {
				$this->mimeType[$this->storage->getId()][$this->path] = 'httpd/unix-directory';
				return $this->mimeType[$this->storage->getId()][$this->path];
			}

			if ($this->request->getMethod() === 'PUT' || $this->request->getMethod() === 'MOVE') {
				if ($this->request->getMethod() === 'MOVE') {
					$this->mimeType[$this->storage->getId()][$this->path] = $this->mimeTypeDetector->detectPath($this->path);
				} else {
					$path = $this->request->getPathInfo();
					$this->mimeType[$this->storage->getId()][$this->path] = $this->mimeTypeDetector->detectPath($path);
				}
				return $this->mimeType[$this->storage->getId()][$this->path];
			}
		} else if ($this->isPublicWebDAVRequest()) {
			if ($this->request->getMethod() === 'PUT') {
				$path = $this->request->getPathInfo();
				if (strpos($path, '/webdav/') === 0) {
					$path = substr($path, strlen('/webdav'));
				}
				$path = $this->path . $path;
				$this->mimeType[$this->storage->getId()][$path] = $this->mimeTypeDetector->detectPath($path);
				return $this->mimeType[$this->storage->getId()][$path];
			}
		}

		if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
			$files = $this->request->getUploadedFile('files');
			if (isset($files['type'][0])) {
				$mimeType = $files['type'][0];
				if ($this->mimeType === 'application/octet-stream') {
					// Maybe not...
					$mimeTypeTest = $this->mimeTypeDetector->detectPath($files['name'][0]);
					if ($mimeTypeTest !== 'application/octet-stream' && $mimeTypeTest !== false) {
						$mimeType = $mimeTypeTest;
					} else {
						$mimeTypeTest = $this->mimeTypeDetector->detect($files['tmp_name'][0]);
						if ($mimeTypeTest !== 'application/octet-stream' && $mimeTypeTest !== false) {
							$mimeType = $mimeTypeTest;
						}
					}
				}
				$this->mimeType[$this->storage->getId()][$this->path] = $mimeType;
				return $mimeType;
			}
		}

		$this->mimeType[$this->storage->getId()][$this->path] = $this->storage->getMimeType($this->path);
		if ($this->mimeType[$this->storage->getId()][$this->path] === 'application/octet-stream') {
			$this->mimeType[$this->storage->getId()][$this->path] = $this->detectMimetypeFromPath();
		}

		return $this->mimeType[$this->storage->getId()][$this->path];
	}

	/**
	 * @return string
	 */
	protected function detectMimetypeFromPath() {
		$mimeType = $this->mimeTypeDetector->detectPath($this->path);
		if ($mimeType !== 'application/octet-stream' && $mimeType !== false) {
			return $mimeType;
		}

		if ($this->storage->instanceOfStorage('\OC\Files\Storage\Local')
			|| $this->storage->instanceOfStorage('\OC\Files\Storage\Home')
			|| $this->storage->instanceOfStorage('\OC\Files\ObjectStore\HomeObjectStoreStorage')) {
			$localFile = $this->storage->getLocalFile($this->path);
			if ($localFile !== false) {
				$mimeType = $this->mimeTypeDetector->detect($localFile);
				if ($mimeType !== false) {
					return $mimeType;
				}
			}

			return 'application/octet-stream';
		} else {
			$handle = $this->storage->fopen($this->path, 'r');
			$data = fread($handle, 8024);
			fclose($handle);
			$mimeType = $this->mimeTypeDetector->detectString($data);
			if ($mimeType !== false) {
				return $mimeType;
			}

			return 'application/octet-stream';
		}
	}

	/**
	 * @return bool
	 */
	protected function isWebDAVRequest() {
		return substr($this->request->getScriptName(), 0 - strlen('/remote.php')) === '/remote.php' && (
			$this->request->getPathInfo() === '/webdav' ||
			strpos($this->request->getPathInfo(), '/webdav/') === 0 ||
			$this->request->getPathInfo() === '/dav/files' ||
			strpos($this->request->getPathInfo(), '/dav/files/') === 0 ||
			$this->request->getPathInfo() === '/dav/uploads' ||
			strpos($this->request->getPathInfo(), '/dav/uploads/') === 0
		);
	}

	/**
	 * @return bool
	 */
	protected function isPublicWebDAVRequest() {
		return substr($this->request->getScriptName(), 0 - strlen('/public.php')) === '/public.php' && (
			$this->request->getPathInfo() === '/webdav' ||
			strpos($this->request->getPathInfo(), '/webdav/') === 0
		);
	}
}
