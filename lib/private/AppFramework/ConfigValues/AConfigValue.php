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

namespace OC\AppFramework\ConfigValues;

use OCP\AppFramework\ConfigValues\IConfigValue;

abstract class AConfigValue implements IConfigValue {
	private ?string $default = null;
	private bool $lazy = false;
	private bool $sensitive = false;
	private bool $deprecated = false;

	public function __construct(
		private string $key,
		private int $valueType
	) {
	}

	abstract public function getConfigType(): string;

	public function getKey(): string {
		return $this->key;
	}

	public function getValueType(): int {
		return $this->valueType;
	}

	public function withDefaultString(string $default): self {
		$this->default = $default;
		return $this;
	}

	public function withDefaultInt(int $default): self {
		$this->default = (string) $default;
		return $this;
	}

	public function withDefaultFloat(float $default): self {
		$this->default = (string) $default;
		return $this;
	}

	public function withDefaultBool(bool $default): self {
		$this->default = ($default) ? '1' : '0';
		return $this;
	}

	public function withDefaultArray(array $default): self {
		$this->default = json_encode($default);
		return $this;
	}

	public function getDefault(): ?string {
		return $this->default;
	}

	public function asLazy(bool $lazy = true): self {
		$this->lazy = $lazy;
		return $this;
	}

	public function isLazy(): bool {
		return $this->lazy;
	}

	public function asSensitive(bool $sensitive = true): self {
		$this->sensitive = $sensitive;
		return $this;
	}

	public function isSensitive(): bool {
		return $this->sensitive;
	}

	public function asDeprecated(bool $deprecated = true): self {
		$this->deprecated = $deprecated;
		return $this;
	}

	public function isDeprecated(): bool {
		return $this->deprecated;
	}
}
