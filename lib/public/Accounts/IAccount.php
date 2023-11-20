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

use Generator;
use OCP\IUser;

/**
 * Interface IAccount
 *
 * @since 15.0.0
 */
interface IAccount extends \JsonSerializable {
	/**
	 * Set a property with data
	 *
	 * @since 15.0.0
	 *
	 * @param string $property  Must be one of the PROPERTY_ prefixed constants of \OCP\Accounts\IAccountManager
	 * @param string $value
	 * @param string $scope Must be one of the VISIBILITY_ prefixed constants of \OCP\Accounts\IAccountManager
	 * @param string $verified \OCP\Accounts\IAccountManager::NOT_VERIFIED | \OCP\Accounts\IAccountManager::VERIFICATION_IN_PROGRESS | \OCP\Accounts\IAccountManager::VERIFIED
	 * @param string $verificationData Optional, defaults to empty string. Since @22.0.0.
	 * @return IAccount
	 */
	public function setProperty(string $property, string $value, string $scope, string $verified, string $verificationData = ''): IAccount;

	/**
	 * Get a property by its key
	 *
	 * @since 15.0.0
	 *
	 * @param string $property Must be one of the PROPERTY_ prefixed constants of \OCP\Accounts\IAccountManager
	 * @return IAccountProperty
	 * @throws PropertyDoesNotExistException
	 */
	public function getProperty(string $property): IAccountProperty;

	/**
	 * Get all properties of an account. Array indices are property names.
	 * Values from IAccountPropertyCollections are not included in the return
	 * array.
	 *
	 * @since 15.0.0
	 * @deprecated 22.0.0 use getAllProperties()
	 */
	public function getProperties(): array;

	/**
	 * Set all properties of an account
	 *
	 * @param array<string, array<string, string>>|array<string, array<int, array<string, string>>> $properties
	 *
	 * e.g. `[
	 *   'displayname' => [
	 *     'name' => 'displayname',
	 *     'value' => 'Jonathan Smith',
	 *     'scope' => 'v2-federated',
	 *     'verified' => '0',
	 *     'verificationData' => '',
	 *   ],
	 *   'email' => [
	 *     'name' => 'email',
	 *     'value' => 'jonathan@example.org',
	 *     'scope' => 'v2-federated',
	 *     'verified' => '0',
	 *     'verificationData' => '',
	 *   ],
	 *   // ...
	 *   'additional_mail' => [
	 *     [
	 *       'name' => 'additional_mail',
	 *       'value' => 'jon@example.org',
	 *       'scope' => 'v2-local',
	 *       'verified' => '0',
	 *       'verificationData' => '',
	 *     ],
	 *     [
	 *       'name' => 'additional_mail',
	 *       'value' => 'jon@earth.org',
	 *       'scope' => 'v2-local',
	 *       'verified' => '0',
	 *       'verificationData' => '',
	 *     ],
	 *   ],
	 * ]`
	 *
	 * @since 24.0.0
	 */
	public function setAllPropertiesFromJson(array $properties): IAccount;

	/**
	 * Get all properties of an account. Array indices are numeric. To get
	 * the property name, call getName() against the value.
	 *
	 * IAccountPropertyCollections are being flattened into an IAccountProperty
	 * for each value.
	 *
	 * @since 22.0.0
	 *
	 * @return Generator<int, IAccountProperty>
	 */
	public function getAllProperties(): Generator;

	/**
	 * Set a property collection (multi-value properties)
	 *
	 * @since 22.0.0
	 */
	public function setPropertyCollection(IAccountPropertyCollection $propertyCollection): IAccount;

	/**
	 * Returns the requested property collection (multi-value properties)
	 *
	 * @throws PropertyDoesNotExistException against invalid collection name
	 * @since 22.0.0
	 */
	public function getPropertyCollection(string $propertyCollectionName): IAccountPropertyCollection;

	/**
	 * Get all properties that match the provided filters for scope and verification status
	 *
	 * Since 22.0.0 values from IAccountPropertyCollection are included, but also
	 * as IAccountProperty instances. They for properties of IAccountPropertyCollection are
	 * suffixed incrementally, i.e. #0, #1 ... #n – the numbers have no further meaning.
	 *
	 * @since 15.0.0
	 *
	 * @param string $scope Must be one of the VISIBILITY_ prefixed constants of \OCP\Accounts\IAccountManager
	 * @param string $verified \OCP\Accounts\IAccountManager::NOT_VERIFIED | \OCP\Accounts\IAccountManager::VERIFICATION_IN_PROGRESS | \OCP\Accounts\IAccountManager::VERIFIED
	 * @return IAccountProperty[]
	 */
	public function getFilteredProperties(string $scope = null, string $verified = null): array;

	/**
	 * Get the related user for the account data
	 *
	 * @since 15.0.0
	 *
	 * @return IUser
	 */
	public function getUser(): IUser;
}
