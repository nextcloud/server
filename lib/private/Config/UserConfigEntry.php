<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Config;

use OCP\Config\IUserConfig;
use OCP\Config\ValueType;
use OCP\Security\ICrypto;

class UserConfigEntry {
	private const ENCRYPTION_PREFIX = '$UserConfigEncryption$';
	private const ENCRYPTION_PREFIX_LENGTH = 22; // strlen(self::ENCRYPTION_PREFIX)

	/** @var string $decryptedValue Cached decrypted value, if any */
	private ?string $decryptedValue = null;

	public function __construct(
		private ValueType $type,
		private int $flags,
		private string $value,
		private ICrypto $crypto,
	) {
	}

	public function getRawValue(): string {
		return $this->value;
	}

	public function getType(): ValueType {
		return $this->type;
	}

	public function setType(ValueType $type): void {
		$this->type = $type;
	}

	public function getFlags(): int {
		return $this->flags;
	}

	public function isFlagged(int $mask): bool {
		return (($mask & $this->flags) === $mask);
	}

	public function isSensitive(): bool {
		return $this->isFlagged(IUserConfig::FLAG_SENSITIVE);
	}

	public function isIndexed(): bool {
		return $this->isFlagged(IUserConfig::FLAG_INDEXED);
	}

	/**
	 * will change referenced $value with the decrypted value in case of encrypted (sensitive value)
	 *
	 * @param array{type: ValueType, flags: int, value: string} $valueDetail
	 */
	public function getDecryptedSensitiveValue(): string {
		if (!$this->isFlagged(IUserConfig::FLAG_SENSITIVE)) {
			return $this->value;
		}

		if ($this->decryptedValue !== null) {
			return $this->decryptedValue;
		}

		if (!str_starts_with($this->value, self::ENCRYPTION_PREFIX)) {
			return $this->value;
		}

		$this->decryptedValue = $this->crypto->decrypt(substr($this->value, self::ENCRYPTION_PREFIX_LENGTH));
		return $this->decryptedValue;
	}

	public function setSensitive(bool $sensitive): void {
		if ($sensitive) {
			$this->flags |= IUserConfig::FLAG_SENSITIVE;
			$this->decryptedValue = $this->value;
			$this->value = self::ENCRYPTION_PREFIX . $this->crypto->encrypt($this->value);
		} else {
			$clearValue = $this->getDecryptedSensitiveValue();
			$this->flags &= ~IUserConfig::FLAG_SENSITIVE;
			$this->value = $clearValue;
			$this->decryptedValue = null;
		}
	}

	public function setIndexed(bool $indexed): void {
		if ($indexed) {
			$this->flags |= IUserConfig::FLAG_INDEXED;
			$this->decryptedValue = $this->value;
			$this->value = self::ENCRYPTION_PREFIX . $this->crypto->encrypt($this->value);
		} else {
			$clearValue = $this->getDecryptedSensitiveValue();
			$this->flags &= ~IUserConfig::FLAG_INDEXED;
			$this->value = $clearValue;
			$this->decryptedValue = null;
		}
	}
}
