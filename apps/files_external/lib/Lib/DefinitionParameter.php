<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

/**
 * Parameter for an external storage definition
 */
class DefinitionParameter implements \JsonSerializable {
	// placeholder value for password fields, when the client updates a storage configuration
	// placeholder values are ignored and the field is left unmodified
	public const UNMODIFIED_PLACEHOLDER = '__unmodified__';

	/** Value constants */
	public const VALUE_TEXT = 0;
	public const VALUE_BOOLEAN = 1;
	public const VALUE_PASSWORD = 2;

	/** Flag constants */
	public const FLAG_NONE = 0;
	public const FLAG_OPTIONAL = 1;
	public const FLAG_USER_PROVIDED = 2;
	public const FLAG_HIDDEN = 4;

	/** @var string human-readable parameter tooltip */
	private string $tooltip = '';

	/** @var int value type, see self::VALUE_* constants */
	private int $type = self::VALUE_TEXT;

	/** @var int flags, see self::FLAG_* constants */
	private int $flags = self::FLAG_NONE;

	/**
	 * @param string $name parameter name
	 * @param string $text parameter description
	 * @param mixed $defaultValue default value
	 */
	public function __construct(
		private string $name,
		private string $text,
		private $defaultValue = null,
	) {
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getText(): string {
		return $this->text;
	}

	/**
	 * Get value type
	 *
	 * @return int
	 */
	public function getType(): int {
		return $this->type;
	}

	/**
	 * Set value type
	 *
	 * @param int $type
	 * @return self
	 */
	public function setType(int $type) {
		$this->type = $type;
		return $this;
	}

	/**
	 * @return mixed default value
	 */
	public function getDefaultValue() {
		return $this->defaultValue;
	}

	/**
	 * @param mixed $defaultValue default value
	 * @return self
	 */
	public function setDefaultValue($defaultValue) {
		$this->defaultValue = $defaultValue;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTypeName(): string {
		switch ($this->type) {
			case self::VALUE_BOOLEAN:
				return 'boolean';
			case self::VALUE_TEXT:
				return 'text';
			case self::VALUE_PASSWORD:
				return 'password';
			default:
				return 'unknown';
		}
	}

	/**
	 * @return int
	 */
	public function getFlags(): int {
		return $this->flags;
	}

	/**
	 * @param int $flags
	 * @return self
	 */
	public function setFlags(int $flags) {
		$this->flags = $flags;
		return $this;
	}

	/**
	 * @param int $flag
	 * @return self
	 */
	public function setFlag(int $flag) {
		$this->flags |= $flag;
		return $this;
	}

	/**
	 * @param int $flag
	 * @return bool
	 */
	public function isFlagSet(int $flag): bool {
		return (bool)($this->flags & $flag);
	}

	/**
	 * @return string
	 */
	public function getTooltip(): string {
		return $this->tooltip;
	}

	/**
	 * @param string $tooltip
	 * @return self
	 */
	public function setTooltip(string $tooltip) {
		$this->tooltip = $tooltip;
		return $this;
	}

	/**
	 * Serialize into JSON for client-side JS
	 */
	public function jsonSerialize(): array {
		$result = [
			'value' => $this->getText(),
			'flags' => $this->getFlags(),
			'type' => $this->getType(),
			'tooltip' => $this->getTooltip(),
		];
		$defaultValue = $this->getDefaultValue();
		if ($defaultValue) {
			$result['defaultValue'] = $defaultValue;
		}
		return $result;
	}

	public function isOptional(): bool {
		return $this->isFlagSet(self::FLAG_OPTIONAL) || $this->isFlagSet(self::FLAG_USER_PROVIDED);
	}

	/**
	 * Validate a parameter value against this
	 * Convert type as necessary
	 *
	 * @param mixed $value Value to check
	 * @return bool success
	 */
	public function validateValue(&$value): bool {
		switch ($this->getType()) {
			case self::VALUE_BOOLEAN:
				if (!is_bool($value)) {
					switch ($value) {
						case 'true':
							$value = true;
							break;
						case 'false':
							$value = false;
							break;
						default:
							return false;
					}
				}
				break;
			default:
				if (!$value && !$this->isOptional()) {
					return false;
				}
				break;
		}
		return true;
	}
}
