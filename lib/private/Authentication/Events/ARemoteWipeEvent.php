<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Events;

use OC\Authentication\Token\IToken;
use OCP\EventDispatcher\Event;

abstract class ARemoteWipeEvent extends Event {
	public function __construct(
		private IToken $token,
	) {
		parent::__construct();
	}

	public function getToken(): IToken {
		return $this->token;
	}
}
