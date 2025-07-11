<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Paginate;

use Override;

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

		$writer = new ArrayWriter();
		foreach ($current as $key => &$value) {
			if (!is_array($value)) {
				continue;
			}

			$writer->openMemory();
			$writer->startElement($key);
			$writer->write($value);
			$writer->endElement();
			$value = $writer->getDocument()[0]['value'];
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
