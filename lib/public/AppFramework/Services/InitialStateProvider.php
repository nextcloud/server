<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Services;

/**
 * @since 21.0.0
 */
abstract class InitialStateProvider implements \JsonSerializable {
	/**
	 * @since 21.0.0
	 */
	abstract public function getKey(): string;

	/**
	 * @since 21.0.0
	 */
	abstract public function getData();

	/**
	 * @since 21.0.0
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	final public function jsonSerialize() {
		return $this->getData();
	}
}
