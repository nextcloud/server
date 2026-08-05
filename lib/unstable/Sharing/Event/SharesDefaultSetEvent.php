<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Sharing\Event;

use OCP\EventDispatcher\Event;
use NCU\Sharing\Share;

/**
 * Emitted when a default value for a permission or property has been applied to an existing share
 *
 * @experimental 35.0.0
 */
class SharesDefaultSetEvent extends Event {
	/** @var Share[] */
	private array $shares;

	/**
	 * @param Share[] $shares
	 * @experimental 35.0.0
	 */
	public function __construct(array $shares) {
		parent::__construct();

		$this->shares = $shares;
	}

	/**
	 * @return Share[]
	 * @experimental 35.0.0
	 */
	public function getShares(): array {
		return $this->shares;
	}

	/**
	 * @param Share[] $shares
	 * @experimental 35.0.0
	 */
	public function setShares(array $shares): void {
		$this->shares = $shares;
	}
}
