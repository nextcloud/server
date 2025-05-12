<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Lock;

/**
 * Class LockedException
 *
 * @since 8.1.0
 */
class LockedException extends \Exception {
	/**
	 * Locked path
	 *
	 * @var string
	 */
	private $path;

	/** @var string|null */
	private $existingLock;

	private ?string $readablePath;

	/**
	 * LockedException constructor.
	 *
	 * @param string $path locked path
	 * @param \Exception|null $previous previous exception for cascading
	 * @param string $existingLock since 14.0.0
	 * @param string $readablePath since 20.0.0
	 * @since 8.1.0
	 */
	public function __construct(string $path, ?\Exception $previous = null, ?string $existingLock = null, ?string $readablePath = null) {
		$this->readablePath = $readablePath;
		if ($readablePath) {
			$message = "\"$path\"(\"$readablePath\") is locked";
		} else {
			$message = '"' . $path . '" is locked';
		}
		$this->existingLock = $existingLock;
		if ($existingLock) {
			$message .= ', existing lock on file: ' . $existingLock;
		}
		parent::__construct($message, 0, $previous);
		$this->path = $path;
	}

	/**
	 * @return string
	 * @since 8.1.0
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @return string
	 * @since 19.0.0
	 */
	public function getExistingLock(): ?string {
		return $this->existingLock;
	}

	/**
	 * @return ?string
	 * @since 32.0.0
	 */
	public function getReadablePath(): ?string {
		return $this->readablePath;
	}

}
