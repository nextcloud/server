<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;

/**
 * This event is triggered when a user tries to download a file
 * directly.
 *
 * @since 25.0.0
 */
class BeforeDirectFileDownloadEvent extends Event {
	private string $path;
	private bool $successful = true;
	private ?string $errorMessage = null;

	/**
	 * @since 25.0.0
	 */
	public function __construct(string $path) {
		parent::__construct();
		$this->path = $path;
	}

	/**
	 * @since 25.0.0
	 */
	public function getPath(): string {
		return $this->path;
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
