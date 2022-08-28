<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @license AGPL-3.0
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
	public const VALUE_HIDDEN = 3;

	/** Flag constants */
	public const FLAG_NONE = 0;
	public const FLAG_OPTIONAL = 1;
	public const FLAG_USER_PROVIDED = 2;

	/** @var string name of parameter */
	private string $name;

	/** @var string human-readable parameter text */
	private string $text;

	/** @var string human-readable parameter tooltip */
	private string $tooltip = '';

	/** @var int value type, see self::VALUE_* constants */
	private int $type = self::VALUE_TEXT;

	/** @var int flags, see self::FLAG_* constants */
	private int $flags = self::FLAG_NONE;

	/** @var mixed */
	private $defaultValue;

	/**
	 * @param string $name parameter name
	 * @param string $text parameter description
	 * @param mixed $defaultValue default value
	 */
	public function __construct(string $name, string $text, $defaultValue = null) {
		$this->name = $name;
		$this->text = $text;
		$this->defaultValue = $defaultValue;
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
