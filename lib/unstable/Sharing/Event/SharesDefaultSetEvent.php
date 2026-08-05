<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Sharing\Event;

use NCU\Sharing\Share;
use OCP\EventDispatcher\Event;

/**
 * Emitted when a default value for a permission or property has been applied to an existing share
 *
 * @experimental 35.0.0
 */
final class SharesDefaultSetEvent extends Event {
	/**
	 * @param non-empty-list<Share> $shares
	 * @experimental 35.0.0
	 */
	public function __construct(
		private array $shares,
	) {
		parent::__construct();
	}

	/**
	 * @return non-empty-list<Share>
	 * @experimental 35.0.0
	 */
	public function getShares(): array {
		return $this->shares;
	}

	/**
	 * @param non-empty-list<Share> $shares
	 * @experimental 35.0.0
	 */
	public function setShares(array $shares): void {
		$this->shares = $shares;
	}
}
