<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\Events;

use OCP\EventDispatcher\Event;

abstract class AUserTokensInvalidationEvent extends Event {
	public function __construct(
		protected string $uid,
	) {
		parent::__construct();
	}

	public function getUserId(): string {
		return $this->uid;
	}
}
