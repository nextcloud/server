<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\DB\QueryBuilder;

use OCP\DB\QueryBuilder\ICompositeExpression;

class CompositeExpression implements ICompositeExpression, \Countable {
	public const TYPE_AND = 'AND';
	public const TYPE_OR = 'OR';

	public function __construct(
		private string $type,
		private array  $parts = []
	) {
	}

	public function addMultiple(array $parts = []): ICompositeExpression {
		foreach ($parts as $part) {
			$this->add($part);
		}

		return $this;
	}

	public function add($part): ICompositeExpression {
		if ($part === null) {
			return $this;
		}

		if ($part instanceof self && count($part) === 0) {
			return $this;
		}

		$this->parts[] = $part;

		return $this;
	}

	public function count(): int {
		return count($this->parts);
	}

	public function getType(): string {
		return $this->type;
	}

	/**
	 * Retrieves the string representation of this composite expression.
	 *
	 * @return string
	 */
	public function __toString(): string {
		if ($this->count() === 1) {
			return (string)$this->parts[0];
		}
		return '(' . implode(') ' . $this->type . ' (', $this->parts) . ')';
	}

	public function getParts(): array {
		return $this->parts;
	}
}
