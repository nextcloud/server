<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Template;

class Field implements \JsonSerializable {
	private int $index;
	private string $content;
	private FieldType $type;
	private ?int $id;
	private ?string $tag;

	public function __construct($index, $content, $type, $id = null, $tag = null) {
		$this->index = $index;
		$this->id = $id;
		$this->tag = $tag;

		// TODO: Sanitize content
		$this->content = $content;

		if ($type instanceof FieldType) {
			$this->type = $type;
		} else {
			// TODO: Throw a proper enum with descriptive message
			$this->type = FieldType::tryFrom($type) ?? throw new \Exception();
		}
	}

	public function jsonSerialize(): array {
		return [
			"index" => $this->index,
			"content" => $this->content,
			"type" => $this->type->value,
			"id" => $this->id,
			"tag" => $this->tag,
		];
	}
}
