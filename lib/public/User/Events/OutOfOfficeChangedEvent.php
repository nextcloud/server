<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Events;

use OCP\EventDispatcher\Event;
use OCP\User\IOutOfOfficeData;

/**
 * Emitted when a user's out-of-office period has changed
 *
 * @since 28.0.0
 */
class OutOfOfficeChangedEvent extends Event {
	/**
	 * @since 28.0.0
	 */
	public function __construct(
		private IOutOfOfficeData $data,
	) {
		parent::__construct();
	}

	/**
	 * @since 28.0.0
	 */
	public function getData(): IOutOfOfficeData {
		return $this->data;
	}
}
