<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share\Events;

use OCP\EventDispatcher\Event;
use OCP\Share\IShare;

/**
 * @since 28.0.0
 */
class BeforeShareDeletedEvent extends Event {
	/**
	 * @since 28.0.0
	 */
	public function __construct(
		private IShare $share,
	) {
		parent::__construct();
	}

	/**
	 * @since 28.0.0
	 */
	public function getShare(): IShare {
		return $this->share;
	}
}
