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
 * this allows us to pass the iterator to the cache while keeping a copy
 * of the first X items
 */
class LimitedCopyIterator extends \AppendIterator {
	/** @var array */
	private array $copy = [];

	public function __construct(\Traversable $iterator, int $count) {
		parent::__construct();

		if (!$iterator instanceof \Iterator) {
			$iterator = new \IteratorIterator($iterator);
		}
		$iterator = new \NoRewindIterator($iterator);

		while ($iterator->valid() && count($this->copy) < $count) {
			$this->copy[] = $iterator->current();
			$iterator->next();
		}

		$this->append($this->getFirstItems());
		$this->append($iterator);
	}

	public function getFirstItems(): \Iterator {
		return new \ArrayIterator($this->copy);
	}
}
