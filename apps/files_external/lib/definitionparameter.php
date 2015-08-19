<?php
/**
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_External\Lib;

/**
 * Parameter for an external storage definition
 */
class DefinitionParameter implements \JsonSerializable {

	/** Value constants */
	const VALUE_TEXT = 0;
	const VALUE_BOOLEAN = 1;
	const VALUE_PASSWORD = 2;
	const VALUE_HIDDEN = 3;

	/** Flag constants */
	const FLAG_NONE = 0;
	const FLAG_OPTIONAL = 1;

	/** @var string name of parameter */
	private $name;

	/** @var string human-readable parameter text */
	private $text;

	/** @var int value type, see self::VALUE_* constants */
	private $type = self::VALUE_TEXT;

	/** @var int flags, see self::FLAG_* constants */
	private $flags = self::FLAG_NONE;

	/**
	 * @param string $name
	 * @param string $text
	 */
	public function __construct($name, $text) {
		$this->name = $name;
		$this->text = $text;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getText() {
		return $this->text;
	}

	/**
	 * Get value type
	 *
	 * @return int
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Set value type
	 *
	 * @param int $type
	 * @return self
	 */
	public function setType($type) {
		$this->type = $type;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getFlags() {
		return $this->flags;
	}

	/**
	 * @param int $flags
	 * @return self
	 */
	public function setFlags($flags) {
		$this->flags = $flags;
		return $this;
	}

	/**
	 * @param int $flag
	 * @return self
	 */
	public function setFlag($flag) {
		$this->flags |= $flag;
		return $this;
	}

	/**
	 * @param int $flag
	 * @return bool
	 */
	public function isFlagSet($flag) {
		return (bool) $this->flags & $flag;
	}

	/**
	 * Serialize into JSON for client-side JS
	 *
	 * @return string
	 */
	public function jsonSerialize() {
		$prefix = '';
		switch ($this->getType()) {
			case self::VALUE_BOOLEAN:
				$prefix = '!';
				break;
			case self::VALUE_PASSWORD:
				$prefix = '*';
				break;
			case self::VALUE_HIDDEN:
				$prefix = '#';
				break;
		}

		switch ($this->getFlags()) {
			case self::FLAG_OPTIONAL:
				$prefix = '&' . $prefix;
				break;
		}

		return $prefix . $this->getText();
	}

	/**
	 * Validate a parameter value against this
	 *
	 * @param mixed $value Value to check
	 * @return bool success
	 */
	public function validateValue($value) {
		if ($this->getFlags() & self::FLAG_OPTIONAL) {
			return true;
		}
		switch ($this->getType()) {
		case self::VALUE_BOOLEAN:
			if (!is_bool($value)) {
				return false;
			}
			break;
		default:
			if (empty($value)) {
				return false;
			}
			break;
		}
		return true;
	}
}
