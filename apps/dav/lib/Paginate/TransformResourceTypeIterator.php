<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Paginate;

use Override;
use Sabre\DAV\Xml\Property\ResourceType;

/**
 * @implements \Iterator<int, array>
 */
class TransformResourceTypeIterator implements \Iterator {

	private \Iterator $inner;
	private const RESOURCE_TYPE_PROPERTY = '{DAV:}resourcetype';
	public function __construct(\Traversable $inner) {
		$this->inner = $inner instanceof \Iterator ? $inner : new \IteratorIterator($inner);
	}

	#[Override] public function current(): mixed {
		$current = $this->inner->current();

		foreach ($current as &$value) {
			if (
				!is_array($value)
				|| !array_key_exists(self::RESOURCE_TYPE_PROPERTY, $value)
				|| !$value[self::RESOURCE_TYPE_PROPERTY] instanceof ResourceType
			) {
				continue;
			}

			$resourceTypes = $value[self::RESOURCE_TYPE_PROPERTY]->getValue();
			// replace the object with an array that can be serialized to json
			$value[self::RESOURCE_TYPE_PROPERTY] = array_combine(
				array_values($resourceTypes),
				array_fill(0, count($resourceTypes), null)
			);
		}

		return $current;
	}

	public function next(): void {
		$this->inner->next();
	}

	public function key(): mixed {
		return $this->inner->key();
	}

	public function valid(): bool {
		return $this->inner->valid();
	}

	public function rewind(): void {
		$this->inner->rewind();
	}
}
