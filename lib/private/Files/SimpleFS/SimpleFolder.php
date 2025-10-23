<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\SimpleFS;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;

/**
 * Concrete implementation of {@see \OCP\Files\SimpleFS\ISimpleFolder}.
 *
 * Wraps a Folder object to expose simplified filesystem operations.
 *
 * @internal This class is not part of the public API and may change without notice.
 */
class SimpleFolder implements ISimpleFolder {
	public function __construct(private Folder $folder) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDirectoryListing(): array {
    	$nodes = $this->folder->getDirectoryListing();
    	$files = [];
    	foreach ($nodes as $node) {
        	if ($node instanceof File) {
            	$files[] = new SimpleFile($node);
        	}
    	}
    	return $files;
	}

	/**
	 * {@inheritdoc}
	 *
	 * (NotFoundException|NotPermittedException) are treated equally in the underlying class and not passed up;
	 * Both situations will return false here.
	 */
	public function fileExists(string $name): bool {
		return $this->folder->nodeExists($name);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFile(string $name): ISimpleFile {
		$file = $this->folder->get($name);

		if (!($file instanceof File)) {
			throw new NotFoundException();
		}

		return new SimpleFile($file);
	}

	/**
	 * {@inheritdoc}
	 */
	public function newFile(string $name, $content = null): ISimpleFile {
		if ($content === null) {
			// delay creating the file until it's written to
			return new NewSimpleFile($this->folder, $name);
		} else {
			$file = $this->folder->newFile($name, $content);
			return new SimpleFile($file);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete(): void {
		$this->folder->delete();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName(): string {
		return $this->folder->getName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFolder(string $name): ISimpleFolder {
		$folder = $this->folder->get($name);

		if (!($folder instanceof Folder)) {
			throw new NotFoundException();
		}

		return new SimpleFolder($folder);
	}

	/**
	 * {@inheritdoc}
	 *
	 * (AlreadyExistsException|InvalidPathException) and other storage-specific exceptions may bubble up here.
	 * The implementation does not handle them and the interface does not define if we should.
	 */
	public function newFolder(string $path): ISimpleFolder {
		$folder = $this->folder->newFolder($path);
		return new SimpleFolder($folder);
	}
}
