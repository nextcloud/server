<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Node;

use OCP\Files\GenericFileException;
use OCP\Files\NotPermittedException;
use OCP\Lock\LockedException;

class File extends Node implements \OCP\Files\File {
	/**
	 * Creates a Folder that represents a non-existing path
	 *
	 * @param string $path path
	 * @return NonExistingFile non-existing node
	 */
	protected function createNonExistingNode($path) {
		return new NonExistingFile($this->root, $this->view, $path);
	}

	/**
	 * @return string
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 * @throws LockedException
	 */
	public function getContent() {
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

	/**
	 * @param string|resource $data
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 * @throws LockedException
	 */
	public function putContent($data) {
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

	/**
	 * @param string $mode
	 * @return resource|false
	 * @throws NotPermittedException
	 * @throws LockedException
	 */
	public function fopen($mode) {
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

	/**
	 * @throws NotPermittedException
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */
	public function delete() {
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

	/**
	 * @param string $type
	 * @param bool $raw
	 * @return string
	 */
	public function hash($type, $raw = false) {
		return $this->view->hash($type, $this->path, $raw);
	}

	/**
	 * @inheritdoc
	 */
	public function getChecksum() {
		return $this->getFileInfo()->getChecksum();
	}

	public function getExtension(): string {
		return $this->getFileInfo()->getExtension();
	}
}
