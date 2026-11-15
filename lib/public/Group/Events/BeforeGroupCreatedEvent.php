<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Group\Events;

use OCP\AppFramework\Attribute\Dispatchable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;

/**
 * @since 18.0.0
 */
#[Listenable(since: '18.0.0')]
#[Dispatchable(since: '18.0.0')]
class BeforeGroupCreatedEvent extends Event {
	/**
	 * @since 18.0.0
	 */
	public function __construct(
		private readonly string $name,
	) {
		parent::__construct();
	}

	/**
	 * @return string
	 * @since 18.0.0
	 */
	public function getName(): string {
		return $this->name;
	}
}
