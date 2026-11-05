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
 * This event is triggered before an archive is created when a user requested
 * downloading a folder or multiple files.
 *
 * By setting `successful` to false the tar creation can be aborted and the download denied.
 *
 * If `allowsPartialArchive` returns true, listeners should only block the
 * archive creation if access to the entire directory or all files is denied.
 * Single nodes can be excluded from the archive with `addNodeFilter`.
 * Listeners can always block the whole archive with `setSuccessful(false)`.
 *
 * @since 25.0.0
 */
class BeforeZipCreatedEvent extends Event {
	private string $directory = '';
	private bool $successful = true;
	private ?string $errorMessage = null;
	private ?Folder $folder = null;
	/** @var list<callable(Node): ?string> */
	private array $nodeFilters = [];

	/**
	 * @param string|Folder $directory Folder instance, or (deprecated) string path relative to user folder
	 * @param list<string> $files Selected files, empty for folder selection
	 * @param bool $allowPartialArchive True if excluded or missing files should not block the creation of the archive
	 * @since 25.0.0
	 * @since 31.0.0 support `OCP\Files\Folder` as `$directory` parameter - passing a string is deprecated now
	 * @since 36.0.0 `$allowPartialArchive` parameter
	 */
	public function __construct(
		string|Folder $directory,
		private array $files,
		private bool $allowPartialArchive = false,
	) {
		parent::__construct();
		if ($directory instanceof Folder) {
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
	 * Returns folder path relative to user folder
	 *
	 * @since 25.0.0
	 * @deprecated 33.0.0 Use getFolder instead and use node API
	 */
	public function getDirectory(): string {
		if ($this->folder instanceof Folder) {
			$path = preg_replace('|^/[^/]+/files/|', '/', $this->folder->getPath());
			if ($path === null) {
				throw new \UnexpectedValueException('Could not determine path from folder');
			}
			return $path;
		}
		return $this->directory;
	}

	/**
	 * @since 25.0.0
	 */
	public function getFiles(): array {
		return $this->files;
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
	 * Whether the archive may be created without the nodes excluded by node filters.
	 *
	 * @since 36.0.0
	 */
	public function allowsPartialArchive(): bool {
		return $this->allowPartialArchive;
	}

	/**
	 * Adds a filter deciding whether a node is included in the archive.
	 * Excluding a folder also excludes all of its content.
	 *
	 * @param callable(Node): ?string $filter returns null to include the node,
	 *                                        or the reason for excluding it
	 * @since 36.0.0
	 */
	public function addNodeFilter(callable $filter): void {
		$this->nodeFilters[] = $filter;
	}

	/**
	 * Returns the reason given by the first filter excluding the node, or null
	 * if the node is to be included in the archive.
	 *
	 * @since 36.0.0
	 */
	public function getExclusionReason(Node $node): ?string {
		foreach ($this->nodeFilters as $filter) {
			$reason = $filter($node);
			if ($reason !== null) {
				return $reason;
			}
		}
		return null;
	}

	/**
	 * @since 25.0.0
	 */
	public function setErrorMessage(string $errorMessage): void {
		$this->errorMessage = $errorMessage;
	}
}
