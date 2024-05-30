<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http\Events;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\Event;

/**
 * Emitted before the rendering step of each TemplateResponse. The event holds a
 * flag that specifies if an user is logged in.
 *
 * @since 20.0.0
 */
class BeforeTemplateRenderedEvent extends Event {
	/** @var bool */
	private $loggedIn;
	/** @var TemplateResponse */
	private $response;

	/**
	 * @since 20.0.0
	 */
	public function __construct(bool $loggedIn, TemplateResponse $response) {
		parent::__construct();

		$this->loggedIn = $loggedIn;
		$this->response = $response;
	}

	/**
	 * @since 20.0.0
	 */
	public function isLoggedIn(): bool {
		return $this->loggedIn;
	}

	/**
	 * @since 20.0.0
	 */
	public function getResponse(): TemplateResponse {
		return $this->response;
	}
}
