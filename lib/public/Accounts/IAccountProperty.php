<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\Accounts;

use InvalidArgumentException;

/**
 * Interface IAccountProperty
 *
 * @since 15.0.0
 */
interface IAccountProperty extends \JsonSerializable {
	/**
	 * Set the value of a property
	 *
	 * @since 15.0.0
	 *
	 * @param string $value
	 * @return IAccountProperty
	 */
	public function setValue(string $value): IAccountProperty;

	/**
	 * Set the scope of a property
	 *
	 * @since 15.0.0
	 *
	 * @param string $scope
	 * @return IAccountProperty
	 * @throws InvalidArgumentException (since 22.0.0)
	 */
	public function setScope(string $scope): IAccountProperty;

	/**
	 * Set the verification status of a property
	 *
	 * @since 15.0.0
	 *
	 * @param string $verified
	 * @return IAccountProperty
	 */
	public function setVerified(string $verified): IAccountProperty;

	/**
	 * Get the name of a property
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Get the value of a property
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getValue(): string;

	/**
	 * Get the scope of a property
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getScope(): string;

	/**
	 * Get the verification status of a property
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	public function getVerified(): string;

	/**
	 * Sets data for verification purposes.
	 *
	 * @since 22.0.0
	 */
	public function setVerificationData(string $verificationData): IAccountProperty;

	/**
	 * Retrieves data for verification purposes.
	 *
	 * @since 22.0.0
	 */
	public function getVerificationData(): string;

	/**
	 * Set the instance-based verification status of a property
	 *
	 * @since 23.0.0
	 *
	 * @param string $verified must be one of the verification constants of IAccountManager
	 * @return IAccountProperty
	 * @throws InvalidArgumentException
	 */
	public function setLocallyVerified(string $verified): IAccountProperty;

	/**
	 * Get the instance-based verification status of a property
	 *
	 * @since 23.0.0
	 *
	 * @return string
	 */
	public function getLocallyVerified(): string;
}
