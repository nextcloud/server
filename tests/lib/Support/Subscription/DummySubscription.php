<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Support\Subscription;

use OCP\Support\Subscription\ISubscription;

class DummySubscription implements ISubscription {
	/**
	 * DummySubscription constructor.
	 *
	 * @param bool $hasValidSubscription
	 * @param bool $hasExtendedSupport
	 */
	public function __construct(
		private bool $hasValidSubscription,
		private bool $hasExtendedSupport,
		private bool $isHardUserLimitReached,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function hasValidSubscription(): bool {
		return $this->hasValidSubscription;
	}

	/**
	 * @inheritDoc
	 */
	public function hasExtendedSupport(): bool {
		return $this->hasExtendedSupport;
	}

	public function isHardUserLimitReached(): bool {
		return $this->isHardUserLimitReached;
	}
}
