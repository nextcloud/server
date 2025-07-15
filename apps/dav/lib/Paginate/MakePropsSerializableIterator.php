<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Paginate;

use Override;

/**
 * This iterator converts file properties coming from SabreDAV calls to event
 * handlers of the 'beforeMultiStatus' event to a format that SabreDAV can still
 * serialize to XML, but is also serializable into JSON. This is essential to
 * store them without loss into a cache (e.g. Redis) as JSON strings.
 *
 * @implements \Iterator<int, array>
 */
class MakePropsSerializableIterator implements \Iterator {

	private \Iterator $inner;

	private ArrayWriter $writer;

	public function __construct(
		\Traversable $inner,
		ArrayWriter $writer,
	) {
		$this->inner = $inner instanceof \Iterator ? $inner : new \IteratorIterator($inner);
		$this->writer = $writer;
	}

	#[Override]
	public function current(): array {
		$current = $this->inner->current();

		foreach ($current as &$value) {
			if (!is_array($value)) {
				// no need to handle simple properties like 'href'
				continue;
			}

			foreach ($value as &$property) {
				if (is_object($property)) {
					$property = $this->getSerializable($property);
				}
			}
		}

		return $current;
	}

	public function getSerializable(object $property): mixed {
		$this->writer->openMemory();
		// we need to add a bogus root element that will contain the properties
		$this->writer->startElement('root');
		$this->writer->write($property);
		$this->writer->endElement();
		return $this->writer->getDocument()[0]['value'] ?? [];
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
