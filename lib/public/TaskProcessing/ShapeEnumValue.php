<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\TaskProcessing;

/**
 * Data object for input output shape enum slot value
 * @since 30.0.0
 */
class ShapeEnumValue implements \JsonSerializable {
	/**
	 * @param string $name
	 * @param string $value
	 * @since 30.0.0
	 */
	public function __construct(
		private string $name,
		private string $value,
	) {
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function getValue(): string {
		return $this->value;
	}

	/**
	 * @return array{name: string, value: string}
	 * @since 30.0.0
	 */
	public function jsonSerialize(): array {
		return [
			'name' => $this->getName(),
			'value' => $this->getValue(),
		];
	}
}
