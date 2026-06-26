<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Share\Events;

use OCP\EventDispatcher\Event;
use OCP\Share\IShare;

/**
 * @since 33.0.0
 */
class ShareTransferredEvent extends Event {
	/**
	 * @since 33.0.0
	 */
	public function __construct(
		private readonly IShare $share,
	) {
		parent::__construct();
	}

	/**
	 * @since 33.0.0
	 */
	public function getShare(): IShare {
		return $this->share;
	}
}
