<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Support\Subscription;

class DummySubscription implements \OCP\Support\Subscription\ISubscription {
	/** @var bool */
	private $hasValidSubscription;
	/** @var bool */
	private $hasExtendedSupport;
	/** @var bool */
	private $isHardUserLimitReached;

	/**
	 * DummySubscription constructor.
	 *
	 * @param bool $hasValidSubscription
	 * @param bool $hasExtendedSupport
	 */
	public function __construct(bool $hasValidSubscription, bool $hasExtendedSupport, bool $isHardUserLimitReached) {
		$this->hasValidSubscription = $hasValidSubscription;
		$this->hasExtendedSupport = $hasExtendedSupport;
		$this->isHardUserLimitReached = $isHardUserLimitReached;
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
