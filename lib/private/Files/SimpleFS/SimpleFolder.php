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

class SimpleFolder implements ISimpleFolder {
	/** @var Folder */
	private $folder;

	/**
	 * Folder constructor.
	 *
	 * @param Folder $folder
	 */
	public function __construct(Folder $folder) {
		$this->folder = $folder;
	}

	public function getName(): string {
		return $this->folder->getName();
	}

	public function getDirectoryListing(): array {
		$listing = $this->folder->getDirectoryListing();

		$fileListing = array_map(function (Node $file) {
			if ($file instanceof File) {
				return new SimpleFile($file);
			}
			return null;
		}, $listing);

		$fileListing = array_filter($fileListing);

		return array_values($fileListing);
	}

	public function delete(): void {
		$this->folder->delete();
	}

	public function fileExists(string $name): bool {
		return $this->folder->nodeExists($name);
	}

	public function getFile(string $name): ISimpleFile {
		$file = $this->folder->get($name);

		if (!($file instanceof File)) {
			throw new NotFoundException();
		}

		return new SimpleFile($file);
	}

	public function newFile(string $name, $content = null): ISimpleFile {
		if ($content === null) {
			// delay creating the file until it's written to
			return new NewSimpleFile($this->folder, $name);
		} else {
			$file = $this->folder->newFile($name, $content);
			return new SimpleFile($file);
		}
	}

	public function getFolder(string $name): ISimpleFolder {
		$folder = $this->folder->get($name);

		if (!($folder instanceof Folder)) {
			throw new NotFoundException();
		}

		return new SimpleFolder($folder);
	}

	public function newFolder(string $path): ISimpleFolder {
		$folder = $this->folder->newFolder($path);
		return new SimpleFolder($folder);
	}
}
