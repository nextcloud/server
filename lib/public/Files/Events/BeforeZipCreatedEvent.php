<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;

/**
 * @since 25.0.0
 */
class BeforeZipCreatedEvent extends Event {
	private string $directory;
	private array $files;
	private bool $successful = true;
	private ?string $errorMessage = null;

	/**
	 * @since 25.0.0
	 */
	public function __construct(string $directory, array $files) {
		parent::__construct();
		$this->directory = $directory;
		$this->files = $files;
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
