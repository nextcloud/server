<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\SimpleFS;

use OCP\Files\File;
use OCP\Files\GenericFileException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Lock\LockedException;

class SimpleFile implements ISimpleFile {
	public function __construct(
		private File $file,
	) {
	}

	/**
	 * Get the name
	 */
	public function getName(): string {
		return $this->file->getName();
	}

	/**
	 * Get the size in bytes
	 */
	public function getSize(): int|float {
		return $this->file->getSize();
	}

	/**
	 * Get the ETag
	 */
	public function getETag(): string {
		return $this->file->getEtag();
	}

	/**
	 * Get the last modification time
	 */
	public function getMTime(): int {
		return $this->file->getMTime();
	}

	/**
	 * Get the content
	 *
	 * @throws GenericFileException
	 * @throws LockedException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function getContent(): string {
		$result = $this->file->getContent();

		if ($result === false) {
			$this->checkFile();
		}

		return $result;
	}

	/**
	 * Overwrite the file
	 *
	 * @param string|resource $data
	 * @throws GenericFileException
	 * @throws LockedException
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 */
	public function putContent($data): void {
		try {
			$this->file->putContent($data);
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
		$this->file->delete();
	}

	/**
	 * Get the MimeType
	 */
	public function getMimeType(): string {
		return $this->file->getMimeType();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getExtension(): string {
		return $this->file->getExtension();
	}

	/**
	 * Open the file as stream for reading, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|false
	 * @throws NotPermittedException
	 * @since 14.0.0
	 */
	public function read() {
		return $this->file->fopen('r');
	}

	/**
	 * Open the file as stream for writing, resulting resource can be operated as stream like the result from php's own fopen
	 *
	 * @return resource|false
	 * @throws NotPermittedException
	 * @since 14.0.0
	 */
	public function write() {
		return $this->file->fopen('w');
	}

	public function getId(): int {
		return $this->file->getId();
	}
}
