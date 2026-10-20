<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Authentication\Events;

use OCP\Authentication\Token\IToken;
use OCP\EventDispatcher\Event;

/**
 * Dispatched after an app password was created, from any place that creates one
 *
 * @since 36.0.0
 */
class AppPasswordCreatedEvent extends Event {
	/**
	 * @since 36.0.0
	 */
	public function __construct(
		private IToken $token,
	) {
		parent::__construct();
	}

	/**
	 * @since 36.0.0
	 */
	public function getToken(): IToken {
		return $this->token;
	}
}
