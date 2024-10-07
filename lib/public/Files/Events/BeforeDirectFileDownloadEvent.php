<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Node;

/**
 * This event is triggered when a user tries to download a file directly.
 * Possible reasons are i.a. using the direct-download endpoint or WebDAV `GET` request.
 *
 * By setting `successful` to false the download can be aborted and denied.
 *
 * @since 25.0.0
 */
class BeforeDirectFileDownloadEvent extends Event {
	private string $path;
	private ?Node $node = null;
	private bool $successful = true;
	private ?string $errorMessage = null;

	/**
	 * @since 25.0.0
	 * @since 31.0.0 support `Node` as parameter for $path - passing a string is deprecated now.
	 */
	public function __construct(string|Node $path) {
		parent::__construct();
		if ($path instanceof Node) {
			$this->node = $path;
			$this->path = $path->getPath();
		} else {
			$this->path = $path;
		}
	}

	/**
	 * @since 25.0.0
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @since 31.0.0
	 */
	public function getNode(): ?Node {
		return $this->node;
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
