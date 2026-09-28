<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\SimpleFS;

use Icewind\Streams\CallbackWrapper;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;

class NewSimpleFile implements ISimpleFile {
	private Folder $parentFolder;
	private string $name;
	private ?File $file = null;

	/**
	 * File constructor.
	 */
	public function __construct(Folder $parentFolder, string $name) {
		$this->parentFolder = $parentFolder;
		$this->name = $name;
	}

	/**
	 * Get the name
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get the size in bytes
	 */
	public function getSize(): int|float {
		if ($this->file) {
			return $this->file->getSize();
		} else {
			return 0;
		}
	}

	/**
	 * Get the ETag
	 */
	public function getETag(): string {
		if ($this->file) {
			return $this->file->getEtag();
		} else {
			return '';
		}
	}

	/**
	 * Get the last modification time
	 */
	public function getMTime(): int {
		if ($this->file) {
			return $this->file->getMTime();
		} else {
			return time();
		}
	}

	/**
	 * Get the content
	 *
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function getContent(): string {
		if ($this->file) {
			$result = $this->file->getContent();

			if ($result === false) {
				$this->checkFile();
			}

			return $result;
		} else {
			return '';
		}
	}

	/**
	 * Overwrite the file
	 *
	 * @param string|resource $data
	 * @throws NotPermittedException
	 * @throws NotFoundException
	 */
	public function putContent($data): void {
		try {
			if ($this->file) {
				$this->file->putContent($data);
			} else {
				$this->file = $this->parentFolder->newFile($this->name, $data);
			}
		} catch (NotFoundException $e) {
			$this->checkFile();
		}
	}

	/**
	 * Sometimes there are some issues with the AppData. Most of them are from
	 * user error. But we should handle them gracefully anyway.
	 *
	 * If for some reason the current file can't be found. We remove it.
	 * Then traverse up and check all folders if they exists. This so that the
	 * next request will have a valid appdata structure again.
	 *
	 * @throws NotFoundException
	 */
	private function checkFile(): void {
		if (!$this->file) {
			throw new NotFoundException('File not set');
		}

		$cur = $this->file;

		while ($cur->stat() === false) {
			$parent = $cur->getParent();
			try {
				$cur->delete();
			} catch (NotFoundException $e) {
				// Just continue then
			}
			$cur = $parent;
		}

		if ($cur !== $this->file) {
			throw new NotFoundException('File does not exist');
		}
	}


	/**
	 * Delete the file
	 *
	 * @throws NotPermittedException
	 */
	public function delete(): void {
		if ($this->file) {
			$this->file->delete();
		}
	}

	/**
	 * Get the MimeType
	 *
	 * @return string
	 */
	public function getMimeType(): string {
		if ($this->file) {
			return $this->file->getMimeType();
		} else {
			return 'text/plain';
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExtension(): string {
		if ($this->file) {
			return $this->file->getExtension();
		} else {
			return \pathinfo($this->name, PATHINFO_EXTENSION);
		}
	}

	/**
	 * Open the file as stream for reading, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|false
	 * @throws \OCP\Files\NotPermittedException
	 * @since 14.0.0
	 */
	public function read() {
		if ($this->file) {
			return $this->file->fopen('r');
		} else {
			return fopen('php://temp', 'r');
		}
	}

	/**
	 * Open the file as stream for writing, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|bool
	 * @throws \OCP\Files\NotPermittedException
	 * @since 14.0.0
	 */
	public function write() {
		if ($this->file) {
			return $this->file->fopen('w');
		} else {
			$source = fopen('php://temp', 'w+');
			return CallbackWrapper::wrap($source, null, null, null, null, function () use ($source) {
				rewind($source);
				$this->putContent($source);
			});
		}
	}
}
