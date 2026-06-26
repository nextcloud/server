<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Upload;

use OCA\DAV\Connector\Sabre\File;
use Sabre\DAV\IFile;

class UploadFile implements IFile {
	public function __construct(
		private File $file,
	) {
	}

	#[\Override]
	public function put($data) {
		return $this->file->put($data);
	}

	#[\Override]
	public function get() {
		return $this->file->get();
	}

	public function getId() {
		return $this->file->getId();
	}

	#[\Override]
	public function getContentType() {
		return $this->file->getContentType();
	}

	#[\Override]
	public function getETag() {
		return $this->file->getETag();
	}

	/**
	 * @psalm-suppress ImplementedReturnTypeMismatch \Sabre\DAV\IFile::getSize signature does not support 32bit
	 * @return int|float
	 */
	#[\Override]
	public function getSize() {
		return $this->file->getSize();
	}

	#[\Override]
	public function delete() {
		$this->file->delete();
	}

	#[\Override]
	public function getName() {
		return $this->file->getName();
	}

	#[\Override]
	public function setName($name) {
		$this->file->setName($name);
	}

	#[\Override]
	public function getLastModified() {
		return $this->file->getLastModified();
	}

	public function getInternalPath(): string {
		return $this->file->getInternalPath();
	}

	public function getFile(): File {
		return $this->file;
	}

	public function getNode() {
		return $this->file->getNode();
	}
}
