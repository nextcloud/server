<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Template\Fields;

use OCP\Files\Template\Field;
use OCP\Files\Template\FieldType;

/**
 * @since 30.0.0
 */
class CheckBoxField extends Field {
	private bool $checked = false;

	/**
	 * @since 30.0.0
	 */
	public function __construct(string $index, FieldType $type) {
		parent::__construct($index, $type);
	}

	/**
	 * @since 30.0.0
	 */
	public function setValue(mixed $value): void {
		if (!is_bool($value)) {
			throw new \Exception('Invalid value for checkbox field type');
		}

		$this->checked = $value;
	}

	/**
	 * @return array{
	 *     index: string,
	 *     type: string,
	 *     alias: ?string,
	 *     tag: ?string,
	 *     id: ?int,
	 *     content?: string,
	 *     checked?: bool,
	 * }
	 * @since 30.0.0
	 */
	public function jsonSerialize(): array {
		$jsonProperties = parent::jsonSerialize();

		return array_merge($jsonProperties, ['checked' => $this->checked]);
	}
}
