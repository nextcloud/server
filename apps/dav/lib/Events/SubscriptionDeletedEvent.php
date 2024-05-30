<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class SubscriptionDeletedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class SubscriptionDeletedEvent extends Event {

	/** @var int */
	private $subscriptionId;

	/** @var array */
	private $subscriptionData;

	/** @var array */
	private $shares;

	/**
	 * SubscriptionDeletedEvent constructor.
	 *
	 * @param int $subscriptionId
	 * @param array $subscriptionData
	 * @param array $shares
	 * @since 20.0.0
	 */
	public function __construct(int $subscriptionId,
		array $subscriptionData,
		array $shares) {
		parent::__construct();
		$this->subscriptionId = $subscriptionId;
		$this->subscriptionData = $subscriptionData;
		$this->shares = $shares;
	}

	/**
	 * @return int
	 * @since 20.0.0
	 */
	public function getSubscriptionId(): int {
		return $this->subscriptionId;
	}

	/**
	 * @return array
	 * @since 20.0.0
	 */
	public function getSubscriptionData(): array {
		return $this->subscriptionData;
	}

	/**
	 * @return array
	 * @since 20.0.0
	 */
	public function getShares(): array {
		return $this->shares;
	}
}
