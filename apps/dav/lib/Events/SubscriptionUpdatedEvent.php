<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Events;

use OCP\EventDispatcher\Event;

/**
 * Class SubscriptionUpdatedEvent
 *
 * @package OCA\DAV\Events
 * @since 20.0.0
 */
class SubscriptionUpdatedEvent extends Event {

	/** @var int */
	private $subscriptionId;

	/** @var array */
	private $subscriptionData;

	/** @var array */
	private $shares;

	/** @var array */
	private $mutations;

	/**
	 * SubscriptionUpdatedEvent constructor.
	 *
	 * @param int $subscriptionId
	 * @param array $subscriptionData
	 * @param array $shares
	 * @param array $mutations
	 * @since 20.0.0
	 */
	public function __construct(int $subscriptionId,
		array $subscriptionData,
		array $shares,
		array $mutations) {
		parent::__construct();
		$this->subscriptionId = $subscriptionId;
		$this->subscriptionData = $subscriptionData;
		$this->shares = $shares;
		$this->mutations = $mutations;
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

	/**
	 * @return array
	 * @since 20.0.0
	 */
	public function getMutations(): array {
		return $this->mutations;
	}
}
