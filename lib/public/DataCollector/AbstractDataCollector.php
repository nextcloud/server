<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DataCollector;

/**
 * Children of this class must store the collected data in
 * the data property.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Bernhard Schussek <bschussek@symfony.com>
 * @author Carl Schwan <carl@carlschwan.eu>
 * @since 24.0.0
 */
abstract class AbstractDataCollector implements IDataCollector, \JsonSerializable {
	/** @var array */
	protected $data = [];

	/**
	 * @since 24.0.0
	 */
	public function getName(): string {
		return static::class;
	}

	/**
	 * Reset the state of the profiler. By default it only empties the
	 * $this->data contents, but you can override this method to do
	 * additional cleaning.
	 * @since 24.0.0
	 */
	public function reset(): void {
		$this->data = [];
	}

	/**
	 * @since 24.0.0
	 */
	public function __sleep(): array {
		return ['data'];
	}

	/**
	 * @internal to prevent implementing \Serializable
	 * @since 24.0.0
	 */
	final protected function serialize() {
	}

	/**
	 * @internal to prevent implementing \Serializable
	 * @since 24.0.0
	 */
	final protected function unserialize(string $data) {
	}

	/**
	 * @since 24.0.0
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->data;
	}
}
