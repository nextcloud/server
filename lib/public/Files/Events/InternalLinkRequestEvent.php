<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events;

use OCP\AppFramework\Http\Response;
use OCP\EventDispatcher\Event;

/**
 * @since 32.0.0
 */
class InternalLinkRequestEvent extends Event {
	private ?Response $response = null;
	/**
	 * @since 32.0.0
	 */
	public function __construct(
		private string &$fileId,
	) {
		parent::__construct();
	}

	/**
	 * @since 32.0.0
	 */
	public function setFileId(string $fileId): void {
		$this->fileId = $fileId;
	}

	/**
	 * @since 32.0.0
	 */
	public function getFileId(): string {
		return $this->fileId;
	}

	/**
	 * @since 32.0.0
	 */
	public function setResponse(Response $response): void {
		$this->response = $response;
	}

	/**
	 * @since 32.0.0
	 */
	public function getResponse(): ?Response {
		return $this->response;
	}
}
