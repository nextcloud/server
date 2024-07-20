<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 * @psalm-param IAccountManager::SCOPE_* $scope
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
	 * @psalm-return IAccountManager::SCOPE_*
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
