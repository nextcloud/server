<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license AGPL-3.0 or later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\AppFramework\ConfigValues;

interface IConfigValue {
	public const TYPE_STRING = 1;
	public const TYPE_INT = 2;
	public const TYPE_FLOAT = 3;
	public const TYPE_BOOL = 4;
	public const TYPE_ARRAY = 5;

	public function getConfigType(): string;

	public function getKey(): string;
	public function getValueType(): int;

	public function withDefaultString(string $default): self;
	public function withDefaultInt(int $default): self;
	public function withDefaultFloat(float $default): self;
	public function withDefaultBool(bool $default): self;
	public function withDefaultArray(array $default): self;
	public function getDefault(): ?string;

	public function asLazy(bool $lazy = true): self;
	public function isLazy(): bool;
	public function asSensitive(bool $sensitive = true): self;
	public function isSensitive(): bool;
	public function asDeprecated(bool $deprecated = true): self;
	public function isDeprecated(): bool;
}
