<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Template;

class Field implements \JsonSerializable {
	private string $index;
	private string $content;
	private FieldType $type;
	private ?string $alias;
	private ?int $id;
	private ?string $tag;

	public function __construct($index, $content, $type, $alias = null, $id = null, $tag = null) {
		$this->index = $index;
		$this->alias = $alias;
		$this->id = $id;
		$this->tag = $tag;
		$this->content = $content;

		if ($type instanceof FieldType) {
			$this->type = $type;
		} else {
			$this->type = FieldType::tryFrom($type) ?? throw new InvalidFieldTypeException();
		}
	}

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
