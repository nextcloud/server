<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Folder;
use OCP\Files\Node;

/**
 * This event is triggered before a archive is created when a user requested
 * downloading a folder or multiple files.
 *
 * By setting `successful` to false the tar creation can be aborted and the download denied.
 *
 * @since 25.0.0
 */
class BeforeZipCreatedEvent extends Event {
	private string $directory;
	private bool $successful = true;
	private ?string $errorMessage = null;
	private ?Folder $folder = null;

	/**
	 * @param list<string> $files Selected files, empty for folder selection
	 * @param list<Node> $nodes Recursively collected nodes
	 * @since 25.0.0
	 * @since 31.0.0 support `OCP\Files\Folder` as `$directory` parameter - passing a string is deprecated now
	 */
	public function __construct(
		string|Folder $directory,
		private array $files,
		private array $nodes = [],
	) {
		parent::__construct();
		if ($directory instanceof Folder) {
			$this->directory = $directory->getPath();
			$this->folder = $directory;
		} else {
			$this->directory = $directory;
		}
	}

	/**
	 * @since 31.0.0
	 */
	public function getFolder(): ?Folder {
		return $this->folder;
	}

	/**
	 * @since 25.0.0
	 */
	public function getDirectory(): string {
		return $this->directory;
	}

	/**
	 * @since 25.0.0
	 */
	public function getFiles(): array {
		return $this->files;
	}

	/**
	 * @return Node[]
	 */
	public function getNodes(): array {
		return $this->nodes;
	}

	/**
	 * @param Node[] $nodes
	 */
	public function setNodes(array $nodes): void {
		$this->nodes = $nodes;
	}

	/**
	 * @since 25.0.0
	 */
	public function isSuccessful(): bool {
		return $this->successful;
	}

	/**
	 * Set if the event was successful
	 *
	 * @since 25.0.0
	 */
	public function setSuccessful(bool $successful): void {
		$this->successful = $successful;
	}

	/**
	 * Get the error message, if any
	 * @since 25.0.0
	 */
	public function getErrorMessage(): ?string {
		return $this->errorMessage;
	}

	/**
	 * @since 25.0.0
	 */
	public function setErrorMessage(string $errorMessage): void {
		$this->errorMessage = $errorMessage;
	}
}
