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
 * @since 18.0.0
 */
class ShareCreatedEvent extends Event {
	/** @var IShare */
	private $share;

	/**
	 * @since 18.0.0
	 */
	public function __construct(IShare $share) {
		parent::__construct();

		$this->share = $share;
	}

	/**
	 * @since 18.0.0
	 */
	public function getShare(): IShare {
		return $this->share;
	}
}
