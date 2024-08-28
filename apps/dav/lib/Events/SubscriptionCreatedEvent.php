<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class SubscriptionCreatedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class SubscriptionCreatedEvent extends Event {

	/** @var int */
	private $subscriptionId;

	/** @var array */
	private $subscriptionData;

	/**
	 * SubscriptionCreatedEvent constructor.
	 *
	 * @param int $subscriptionId
	 * @param array $subscriptionData
	 * @since 20.0.0
	 */
	public function __construct(int $subscriptionId,
		array $subscriptionData) {
		parent::__construct();
		$this->subscriptionId = $subscriptionId;
		$this->subscriptionData = $subscriptionData;
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
}
