<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Template;

/**
 * @since 30.0.0
 */
abstract class Field implements \JsonSerializable {
	public ?string $alias = null;
	public ?string $tag = null;
	public ?int $id = null;

	/**
	 * @since 30.0.0
	 */
	public function __construct(
		private string $index,
		private FieldType $type,
	) {
	}

	/**
	 * @since 30.0.0
	 */
	abstract public function setValue(mixed $value): void;

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
		return [
			'index' => $this->index,
			'type' => $this->type->value,
			'alias' => $this->alias,
			'tag' => $this->tag,
			'id' => $this->id,
		];
	}
}
