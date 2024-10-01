<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\EventDispatcher;

use ArrayAccess;
use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;
use function array_key_exists;

/**
 * Class GenericEvent
 *
 * convenience reimplementation of \Symfony\Component\GenericEvent against
 * \OCP\EventDispatcher\Event
 *
 * @since 18.0.0
 * @deprecated 22.0.0 use \OCP\EventDispatcher\Event
 */
class GenericEvent extends Event implements ArrayAccess, IteratorAggregate {
	/** @deprecated 22.0.0 */
	protected $subject;

	/** @deprecated 22.0.0 */
	protected $arguments;

	/**
	 * Encapsulate an event with $subject and $args.
	 *
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function __construct($subject = null, array $arguments = []) {
		parent::__construct();
		$this->subject = $subject;
		$this->arguments = $arguments;
	}

	/**
	 * Getter for subject property.
	 *
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * Get argument by key.
	 *
	 * @throws InvalidArgumentException if key is not found
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function getArgument(string $key) {
		if ($this->hasArgument($key)) {
			return $this->arguments[$key];
		}

		throw new InvalidArgumentException(sprintf('Argument "%s" not found.', $key));
	}

	/**
	 * Add argument to event.
	 *
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function setArgument($key, $value): GenericEvent {
		$this->arguments[$key] = $value;
		return $this;
	}

	/**
	 * Getter for all arguments.
	 *
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function getArguments(): array {
		return $this->arguments;
	}

	/**
	 * Set args property.
	 *
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function setArguments(array $args = []): GenericEvent {
		$this->arguments = $args;
		return $this;
	}

	/**
	 * Has argument.
	 *
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function hasArgument($key): bool {
		return array_key_exists($key, $this->arguments);
	}

	/**
	 * Retrieve an external iterator
	 *
	 * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator($this->arguments);
	}

	/**
	 * Whether a offset exists
	 *
	 * @link https://php.net/manual/en/arrayaccess.offsetexists.php
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function offsetExists($offset): bool {
		return $this->hasArgument($offset);
	}

	/**
	 * Offset to retrieve
	 *
	 * @link https://php.net/manual/en/arrayaccess.offsetget.php
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset) {
		return $this->arguments[$offset];
	}

	/**
	 * Offset to set
	 *
	 * @link https://php.net/manual/en/arrayaccess.offsetset.php
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function offsetSet($offset, $value): void {
		$this->setArgument($offset, $value);
	}

	/**
	 * Offset to unset
	 *
	 * @link https://php.net/manual/en/arrayaccess.offsetunset.php
	 * @since 18.0.0
	 * @deprecated 22.0.0
	 */
	public function offsetUnset($offset): void {
		if ($this->hasArgument($offset)) {
			unset($this->arguments[$offset]);
		}
	}
}
