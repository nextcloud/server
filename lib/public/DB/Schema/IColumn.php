<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\Schema;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Object representation of a column.
 *
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
interface IColumn {
	/**
	 * @param \OCP\DB\Types::*|IType $type
	 * @since 35.0.0
	 */
	public function setType(string|IType $type): self;

	/**
	 * @since 35.0.0
	 */
	public function setLength(?int $length): self;

	/**
	 * @since 35.0.0
	 */
	public function setPrecision(int $precision): self;

	/**
	 * @since 35.0.0
	 */
	public function setScale(int $scale): self;

	/**
	 * @since 35.0.0
	 */
	public function setUnsigned(bool $unsigned): self;

	/**
	 * @since 35.0.0
	 */
	public function setFixed(bool $fixed): self;

	/**
	 * @since 35.0.0
	 */
	public function setNotnull(bool $notnull): self;

	/**
	 * @since 35.0.0
	 */
	public function setDefault(mixed $default): self;

	/**
	 * Returns the type of this column.
	 *
	 * Note that {@see IType::getName()} returns a `\OCP\DB\Types::*` value.
	 *
	 * @since 35.0.0
	 */
	public function getType(): IType;

	/**
	 * @return int|null
	 * @since 35.0.0
	 */
	public function getLength(): ?int;

	/**
	 * @return int
	 * @since 35.0.0
	 */
	public function getPrecision(): int;

	/**
	 * @return int
	 * @since 35.0.0
	 */
	public function getScale(): int;

	/**
	 * @return bool
	 * @since 35.0.0
	 */
	public function getUnsigned(): bool;

	/**
	 * @return bool
	 * @since 35.0.0
	 */
	public function getFixed(): bool;

	/**
	 * @return bool
	 * @since 35.0.0
	 */
	public function getNotnull(): bool;

	/**
	 * @return mixed
	 * @since 35.0.0
	 */
	public function getDefault(): mixed;

	/**
	 * Returns whether this column is an autoincrement column.
	 *
	 * @since 35.0.0
	 */
	public function getAutoincrement(): bool;
}
