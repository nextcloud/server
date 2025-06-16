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
 * A listener can generate its own RedirectResponse() to be sent back to the client.
 *
 * @see ViewController::showFile
 * @since 33.0.0
 */
class InternalLinkRequestEvent extends Event {
	private ?RedirectResponse $response = null;

	/**
	 * @since 33.0.0
	 */
	public function __construct(
		private readonly string $fileId,
	) {
		parent::__construct();
	}

	/**
	 * returns the requested file id
	 *
	 * @since 33.0.0
	 */
	public function getFileId(): string {
		return $this->fileId;
	}

	/**
	 * set a new RedirectResponse
	 *
	 * @since 33.0.0
	 */
	public function setResponse(RedirectResponse $response): void {
		if ($this->response === null) {
			$this->response = $response;
		} else {
			Server::get(LoggerInterface::class)->notice('a RedirectResponse was already set', ['exception' => new \Exception('')]);
		}
	}

	/**
	 * return the new response
	 *
	 * @since 33.0.0
	 */
	public function getResponse(): ?RedirectResponse {
		return $this->response;
	}
}
