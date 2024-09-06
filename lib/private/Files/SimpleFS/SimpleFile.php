<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\SimpleFS;

use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;

class SimpleFile implements ISimpleFile {
	private File $file;

	public function __construct(File $file) {
		$this->file = $file;
	}

	public function getName(): string {
		return $this->file->getName();
	}

	public function getSize(): int|float {
		return $this->file->getSize();
	}

	public function getETag(): string {
		return $this->file->getEtag();
	}

	public function getMTime(): int {
		return $this->file->getMTime();
	}

	public function getContent(): string {
		$result = $this->file->getContent();

		if ($result === false) {
			$this->checkFile();
		}

		return $result;
	}

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


	public function delete(): void {
		$this->file->delete();
	}

	public function getMimeType(): string {
		return $this->file->getMimeType();
	}

	public function getExtension(): string {
		return $this->file->getExtension();
	}

	public function read() {
		return $this->file->fopen('r');
	}

	public function write() {
		return $this->file->fopen('w');
	}

	public function getId(): int {
		return $this->file->getId();
	}
}
