<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\SpeechToText\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\File;

/**
 * @since 27.0.0
 * @deprecated 30.0.0
 */
abstract class AbstractTranscriptionEvent extends Event {
	/**
	 * @since 27.0.0
	 */
	public function __construct(
		private int $fileIdId,
		private ?File $file,
		private ?string $userId,
		private string $appId,
	) {
		parent::__construct();
	}

	/**
	 * @since 27.0.0
	 */
	public function getFileId(): int {
		return $this->fileIdId;
	}

	/**
	 * @since 27.0.0
	 */
	public function getFile(): ?File {
		return $this->file;
	}

	/**
	 * @since 27.0.0
	 */
	public function getUserId(): ?string {
		return $this->userId;
	}

	/**
	 * @since 27.0.0
	 */
	public function getAppId(): string {
		return $this->appId;
	}
}
