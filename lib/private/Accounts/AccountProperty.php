<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Accounts;

use OCP\Accounts\IAccountProperty;

class AccountProperty implements IAccountProperty {

	/** @var string */
	private $name;
	/** @var string */
	private $value;
	/** @var string */
	private $scope;
	/** @var string */
	private $verified;

	public function __construct(string $name, string $value, string $scope, string $verified) {
		$this->name = $name;
		$this->value = $value;
		$this->scope = $scope;
		$this->verified = $verified;
	}

	public function jsonSerialize() {
		return [
			'name' => $this->getName(),
			'value' => $this->getValue(),
			'scope' => $this->getScope(),
			'verified' => $this->getVerified()
		];
	}

	/**
	 * Set the value of a property
	 *
	 * @since 15.0.0
	 *
	 * @param string $value
	 * @return IAccountProperty
	 */
	public function setValue(string $value): IAccountProperty {
		$this->value = $value;
		return $this;
	}

	/**
	 * Set the scope of a property
	 *
	 * @since 15.0.0
	 *
	 * @param string $scope
	 * @return IAccountProperty
	 */
	public function setScope(string $scope): IAccountProperty {
		$this->scope = $scope;
		return $this;
	}

	/**
	 * Set the verification status of a property
	 *
	 * @since 15.0.0
	 *
	 * @param string $verified
	 * @return IAccountProperty
	 */
	public function setVerified(string $verified): IAccountProperty {
		$this->verified = $verified;
		return $this;
	}

	/**
	 * Get the name of a property
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get the value of a property
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getValue(): string {
		return $this->value;
	}

	/**
	 * Get the scope of a property
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getScope(): string {
		return $this->scope;
	}

	/**
	 * Get the verification status of a property
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getVerified(): string {
		return $this->verified;
	}
}
