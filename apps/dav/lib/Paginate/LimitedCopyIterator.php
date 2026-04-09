<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Paginate;

/**
 * Save a copy of the first X items into a separate iterator
 *
 * This allows us to pass the iterator to the cache while keeping a copy
 * of the required items.
 *
 * @extends \AppendIterator<int, int, \Iterator<int, int>>
 */
class LimitedCopyIterator extends \AppendIterator {
	private array $skipped = [];
	private array $copy = [];

	public function __construct(\Traversable $iterator, int $count, int $offset = 0) {
		parent::__construct();

		if (!$iterator instanceof \Iterator) {
			$iterator = new \IteratorIterator($iterator);
		}
		$iterator = new \NoRewindIterator($iterator);

		$i = 0;
		while ($iterator->valid() && ++$i <= $offset) {
			$this->skipped[] = $iterator->current();
			$iterator->next();
		}

		while ($iterator->valid() && count($this->copy) < $count) {
			$this->copy[] = $iterator->current();
			$iterator->next();
		}

		$this->append(new \ArrayIterator($this->skipped));
		$this->append($this->getRequestedItems());
		$this->append($iterator);
	}

	public function getRequestedItems(): \Iterator {
		return new \ArrayIterator($this->copy);
	}
}
