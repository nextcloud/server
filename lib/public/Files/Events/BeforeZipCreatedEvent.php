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
 * If `allowPartialArchive` is set to true, the archive creation should be blocked only
 * if access to the entire directory/all files is to be blocked. To block
 * archiving of certain files only, `addNodeFilter` should be used to add a callable
 * to filter out nodes.
 *
 * @since 25.0.0
 */
class BeforeZipCreatedEvent extends Event {
	private string $directory = '';
	private bool $successful = true;
	private ?string $errorMessage = null;
	private ?Folder $folder = null;
	/** @var iterable<Node>|null */
	private ?iterable $nodesIterable;
	/** @var array<callable(Node): array{0: bool, 1: ?string}> */
	private array $nodeFilters = [];

	/**
	 * @param string|Folder $directory Folder instance, or (deprecated) string path relative to user folder
	 * @param list<string> $files Selected files, empty for folder selection
	 * @param ?bool $allowPartialArchive True if missing/blocked files should not block the creation of the archive
	 * @since 25.0.0
	 * @since 31.0.0 support `OCP\Files\Folder` as `$directory` parameter - passing a string is deprecated now
	 */
	public function __construct(
		string|Folder $directory,
		private array $files,
		public ?bool $allowPartialArchive = true,
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
	 * @since 25.0.0
	 * @deprecated 33.0.0 Use getFolder instead and use node API
	 * @return string returns folder path relative to user folder
	 */
	public function getDirectory(): string {
		if ($this->folder instanceof Folder) {
			return preg_replace('|^/[^/]+/files/|', '/', $this->folder->getPath());
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
	 * Sets the iterable that will be used to yield nodes to be included in the
	 * archive. Nodes can be filtered out by adding filters via `addNodeFilter`.
	 *
	 * @param iterable<Node> $iterable
	 * @return void
	 */
	public function setNodesIterable(iterable $iterable): void {
		$this->nodesIterable = $iterable;
	}

	/**
	 * @param callable(Node): array{0: bool, 1: ?string} $filter filter that
	 * receives a Node and returns an array with a bool telling if the file is
	 * to be included in the archive and an optional reason string.
	 *
	 * @return void
	 */
	public function addNodeFilter(callable $filter): void {
		$this->nodeFilters[] = $filter;
	}

	/**
	 * Returns a generator yielding a string key with the node's path relative
	 * to the downloaded folder and an array which contains a node or null in
	 * the first position (indicating whether the node should be skipped) and a
	 * reason for skipping in the second position.
	 *
	 * @return iterable<string, array{0: ?Node, 1: ?string}>
	 */
	public function getNodes(): iterable {
		if (!isset($this->nodesIterable)) {
			throw new \LogicException('No nodes iterable set');
		}

		if (!$this->successful) {
			return;
		}

		$directory = $this->getDirectory();
		foreach ($this->nodesIterable as $node) {
			$relativePath = $directory . '/' . $node->getName();
			if (!empty($this->files) && !in_array($node->getName(), $this->files)) {
				// the node is supposed to be filtered out
				continue;
			}

			foreach ($this->nodeFilters as $filter) {
				[$include, $reason] = $filter($node);
				if (!$include) {
					yield $relativePath => [null, $reason];
					continue 2;
				}
			}

			yield $relativePath => [$node, null];
		}
	}

	/**
	 * @since 25.0.0
	 */
	public function setErrorMessage(string $errorMessage): void {
		$this->errorMessage = $errorMessage;
	}
}
