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
 * Emitted before the rendering step of the login TemplateResponse.
 *
 * @since 28.0.0
 */
class BeforeLoginTemplateRenderedEvent extends Event {
	/**
	 * @since 28.0.0
	 */
	public function __construct(
		private TemplateResponse $response,
	) {
		parent::__construct();
	}

	/**
	 * @since 28.0.0
	 */
	public function getResponse(): TemplateResponse {
		return $this->response;
	}
}
