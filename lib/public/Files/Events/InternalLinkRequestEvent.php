<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Events;

use OCA\Files\Controller\ViewController;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\EventDispatcher\Event;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Allow a modification of the behavior on internal-link request ('/index.php/f/12345')
 *
 * A listener can change the value of the FileId or even force a new RedirectResponse() to be sent back to the client.
 *
 * @see ViewController::showFile
 * @since 32.0.0
 */
class InternalLinkRequestEvent extends Event {
	private ?RedirectResponse $response = null;
	private ?string $newFileId = null;
	/**
	 * @since 32.0.0
	 */
	public function __construct(
		private readonly string $fileId,
	) {
		parent::__construct();
	}

	/**
	 * returns the original fileId
	 *
	 * @since 32.0.0
	 */
	public function getFileId(): string {
		return $this->fileId;
	}

	/**
	 * Set a new fileId that will be used by the original RedirectResponse
	 *
	 * @since 32.0.0
	 */
	public function setNewFileId(string $fileId): void {
		if ($this->newFileId === null) {
			$this->newFileId = $fileId;
		} else {
			Server::get(LoggerInterface::class)->notice('a new file id was already set', ['exception' => new \Exception('')]);
		}
	}

	/**
	 * return new fileId, or NULL if not defined
	 *
	 * @since 32.0.0
	 */
	public function getNewFileId(): ?string {
		return $this->newFileId;
	}

	/**
	 * set a new RedirectResponse
	 *
	 * @since 32.0.0
	 */
	public function setResponse(RedirectResponse $response): void {
		if ($this->response === null) {
			$this->response = $response;
		} else {
			Server::get(LoggerInterface::class)->notice('a RedirectResponse was already set', ['exception' => new \Exception('')]);
		}
	}

	/**
	 * return the new response to send back to client
	 *
	 * @since 32.0.0
	 */
	public function getResponse(): ?RedirectResponse {
		return $this->response;
	}
}
