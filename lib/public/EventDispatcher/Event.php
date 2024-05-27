<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\EventDispatcher;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Base event class for the event dispatcher service
 *
 * Typically this class isn't instantiated directly but sub classed for specific
 * event types
 *
 * This class extended \Symfony\Contracts\EventDispatcher\Event until 21.0, since
 * 22.0.0 this class directly implements the PSR StoppableEventInterface and no
 * longer relies on Symfony. This transition does not come with any changes in API,
 * the class has the same methods and behavior before and after this change.
 *
 * @since 17.0.0
 */
class Event implements StoppableEventInterface {
	/**
	 * @var bool
	 *
	 * @since 22.0.0
	 */
	private $propagationStopped = false;

	/**
	 * Compatibility constructor
	 *
	 * In Nextcloud 17.0.0 this event class used a now deprecated/removed Symfony base
	 * class that had a constructor (with default arguments). To lower the risk of
	 * a breaking change (PHP won't allow parent constructor calls if there is none),
	 * this empty constructor's only purpose is to hopefully not break existing sub-
	 * classes of this class.
	 *
	 * @since 18.0.0
	 */
	public function __construct() {
	}

	/**
	 * Stops the propagation of the event to further event listeners
	 *
	 * @return void
	 *
	 * @since 22.0.0
	 */
	public function stopPropagation(): void {
		$this->propagationStopped = true;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 22.0.0
	 * @see \Psr\EventDispatcher\StoppableEventInterface
	 */
	public function isPropagationStopped(): bool {
		return $this->propagationStopped;
	}
}
