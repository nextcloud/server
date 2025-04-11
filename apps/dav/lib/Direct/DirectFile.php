<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Direct;

use OCA\DAV\Db\Direct;
use OCA\DAV\Events\BeforeFileDirectDownloadedEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\IFile;

class DirectFile implements IFile {
	/** @var File */
	private $file;

	public function __construct(
		private Direct $direct,
		private IRootFolder $rootFolder,
		private IEventDispatcher $eventDispatcher,
	) {
	}

	public function put($data) {
		throw new Forbidden();
	}

	public function get() {
		$this->getFile();

		$this->eventDispatcher->dispatchTyped(new BeforeFileDirectDownloadedEvent($this->file));

		return $this->file->fopen('rb');
	}

	public function getContentType() {
		$this->getFile();

		return $this->file->getMimeType();
	}

	public function getETag() {
		$this->getFile();

		return $this->file->getEtag();
	}

	/**
	 * @psalm-suppress ImplementedReturnTypeMismatch \Sabre\DAV\IFile::getSize signature does not support 32bit
	 * @return int|float
	 */
	public function getSize() {
		$this->getFile();

		return $this->file->getSize();
	}

	public function delete() {
		throw new Forbidden();
	}

	public function getName() {
		return $this->direct->getToken();
	}

	public function setName($name) {
		throw new Forbidden();
	}

	public function getLastModified() {
		$this->getFile();

		return $this->file->getMTime();
	}

	private function getFile() {
		if ($this->file === null) {
			$userFolder = $this->rootFolder->getUserFolder($this->direct->getUserId());
			$file = $userFolder->getFirstNodeById($this->direct->getFileId());

			if (!$file) {
				throw new NotFound();
			}
			if (!$file instanceof File) {
				throw new Forbidden('direct download not allowed on directories');
			}

			$this->file = $file;
		}

		return $this->file;
	}
}
