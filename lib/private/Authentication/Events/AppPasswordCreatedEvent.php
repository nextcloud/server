<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Events;

use OCP\Authentication\Token\IToken;
use OCP\EventDispatcher\Event;

class AppPasswordCreatedEvent extends Event {
	public function __construct(
		private IToken $token,
	) {
		parent::__construct();
	}

	public function getToken(): IToken {
		return $this->token;
	}
}
