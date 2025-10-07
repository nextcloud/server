<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OCP\Files\GenericFileException;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Override;

class File extends Node implements \OCP\Files\File {
	#[Override]
	protected function createNonExistingNode(string $path): \OCP\Files\Node {
		return new NonExistingFile($this->root, $this->view, $path);
	}

	#[Override]
	public function getContent(): string {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_READ)) {
			$content = $this->view->file_get_contents($this->path);
			if ($content === false) {
				throw new GenericFileException();
			}
			return $content;
		} else {
			throw new NotPermittedException();
		}
	}

	#[Override]
	public function putContent($data): void {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_UPDATE)) {
			$this->sendHooks(['preWrite']);
			if ($this->view->file_put_contents($this->path, $data) === false) {
				throw new GenericFileException('file_put_contents failed');
			}
			$this->fileInfo = null;
			$this->sendHooks(['postWrite']);
		} else {
			throw new NotPermittedException();
		}
	}

	#[Override]
	public function fopen(string $mode) {
		$preHooks = [];
		$postHooks = [];
		$requiredPermissions = \OCP\Constants::PERMISSION_READ;
		switch ($mode) {
			case 'r+':
			case 'rb+':
			case 'w+':
			case 'wb+':
			case 'x+':
			case 'xb+':
			case 'a+':
			case 'ab+':
			case 'w':
			case 'wb':
			case 'x':
			case 'xb':
			case 'a':
			case 'ab':
				$preHooks[] = 'preWrite';
				$postHooks[] = 'postWrite';
				$requiredPermissions |= \OCP\Constants::PERMISSION_UPDATE;
				break;
		}

		if ($this->checkPermissions($requiredPermissions)) {
			$this->sendHooks($preHooks);
			$result = $this->view->fopen($this->path, $mode);
			$this->sendHooks($postHooks);
			return $result;
		} else {
			throw new NotPermittedException();
		}
	}

	#[Override]
	public function delete(): void {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_DELETE)) {
			$this->sendHooks(['preDelete']);
			$fileInfo = $this->getFileInfo();
			$this->view->unlink($this->path);
			$nonExisting = new NonExistingFile($this->root, $this->view, $this->path, $fileInfo);
			$this->sendHooks(['postDelete'], [$nonExisting]);
			$this->fileInfo = null;
		} else {
			throw new NotPermittedException();
		}
	}

	#[Override]
	public function hash(string $type, bool $raw = false): string {
		$hash = $this->view->hash($type, $this->path, $raw);
		if ($hash === false) {
			throw new NotFoundException('Unable to compute hash of non-existent file');
		}
		return $hash;
	}

	#[Override]
	public function getChecksum(): string {
		return $this->getFileInfo()->getChecksum();
	}

	#[Override]
	public function getExtension(): string {
		return $this->getFileInfo()->getExtension();
	}
}
