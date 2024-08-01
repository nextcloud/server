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
class Field implements \JsonSerializable {
	private string $index;
	private string $content;
	private FieldType $type;
	private ?string $alias;
	private ?int $id;
	private ?string $tag;

	/**
	 * @since 30.0.0
	 */
	public function __construct(string $index, string $content, FieldType $type, ?string $alias = null, ?int $id = null, ?string $tag = null) {
		$this->index = $index;
		$this->alias = $alias;
		$this->type = $type;
		$this->id = $id;
		$this->tag = $tag;
		$this->content = $content;
	}

	/**
	 * @since 30.0.0
	 */
	public function jsonSerialize(): array {
		return [
			"index" => $this->index,
			"content" => $this->content,
			"type" => $this->type->value,
			"alias" => $this->alias,
			"id" => $this->id,
			"tag" => $this->tag,
		];
	}
}
