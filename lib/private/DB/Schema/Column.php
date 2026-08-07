<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Schema;

use Doctrine\DBAL\Schema\Column as DBALColumn;
use Doctrine\DBAL\Schema\SchemaException as DBALSchemaException;
use Doctrine\DBAL\Types\Type as DBALType;
use OCP\DB\Schema\IColumn;
use OCP\DB\Schema\IType;
use OCP\DB\Schema\SchemaException;

/**
 * Object representation of a column, wrapping a Doctrine DBAL Column.
 */
class Column implements IColumn {
	public function __construct(
		private DBALColumn $column,
	) {
	}

	/**
	 * Returns the wrapped Doctrine DBAL column.
	 */
	public function getWrappedColumn(): DBALColumn {
		return $this->column;
	}

	#[\Override]
	public function setType(string|IType|DBALType $type): self {
		if ($type instanceof IType) {
			$type = $type->getName();
		}

		$this->column->setType($type instanceof DBALType ? $type : DBALType::getType($type));

		return $this;
	}

	#[\Override]
	public function setLength(?int $length): self {
		$this->column->setLength($length);

		return $this;
	}

	#[\Override]
	public function setPrecision(int $precision): self {
		$this->column->setPrecision($precision);

		return $this;
	}

	#[\Override]
	public function setScale(int $scale): self {
		$this->column->setScale($scale);

		return $this;
	}

	#[\Override]
	public function setUnsigned(bool $unsigned): self {
		$this->column->setUnsigned($unsigned);

		return $this;
	}

	#[\Override]
	public function setFixed(bool $fixed): self {
		$this->column->setFixed($fixed);

		return $this;
	}

	#[\Override]
	public function setNotnull(bool $notnull): self {
		$this->column->setNotnull($notnull);

		return $this;
	}

	#[\Override]
	public function setDefault(mixed $default): self {
		$this->column->setDefault($default);

		return $this;
	}

	#[\Override]
	public function getType(): IType {
		return new Type($this->column->getType());
	}

	#[\Override]
	public function getLength(): ?int {
		return $this->column->getLength();
	}

	#[\Override]
	public function getPrecision(): int {
		return $this->column->getPrecision();
	}

	#[\Override]
	public function getScale(): int {
		return $this->column->getScale();
	}

	#[\Override]
	public function getUnsigned(): bool {
		return $this->column->getUnsigned();
	}

	#[\Override]
	public function getFixed(): bool {
		return $this->column->getFixed();
	}

	#[\Override]
	public function getNotnull(): bool {
		return $this->column->getNotnull();
	}

	#[\Override]
	public function getDefault(): mixed {
		return $this->column->getDefault();
	}

	#[\Override]
	public function getAutoincrement(): bool {
		return $this->column->getAutoincrement();
	}

	/**
	 * Forwards any method not declared on IColumn to the wrapped Doctrine
	 * DBAL column, e.g. read-only accessors like `getName()` or
	 * `getAutoincrement()` that are not part of the public API.
	 */
	public function __call(string $name, array $arguments): mixed {
		try {
			return $this->column->$name(...$arguments);
		} catch (DBALSchemaException $e) {
			throw new SchemaException($e->getMessage(), $e->getCode(), $e);
		}
	}
}
