<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Accounts;

use InvalidArgumentException;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;

class AccountProperty implements IAccountProperty {
	/**
	 * @var IAccountManager::SCOPE_*
	 */
	private string $scope;
	private string $locallyVerified = IAccountManager::NOT_VERIFIED;

	public function __construct(
		private string $name,
		private string $value,
		string $scope,
		private string $verified,
		private string $verificationData,
	) {
		$this->setScope($scope);
	}

	public function jsonSerialize(): array {
		return [
			'name' => $this->getName(),
			'value' => $this->getValue(),
			'scope' => $this->getScope(),
			'verified' => $this->getVerified(),
			'verificationData' => $this->getVerificationData(),
		];
	}

	/**
	 * Set the value of a property
	 *
	 * @since 15.0.0
	 */
	public function setValue(string $value): IAccountProperty {
		$this->value = $value;
		return $this;
	}

	/**
	 * Set the scope of a property
	 *
	 * @since 15.0.0
	 */
	public function setScope(string $scope): IAccountProperty {
		if (!in_array($scope, IAccountManager::ALLOWED_SCOPES, )) {
			throw new InvalidArgumentException('Invalid scope');
		}
		/** @var IAccountManager::SCOPE_* $scope */
		$this->scope = $scope;
		return $this;
	}

	/**
	 * Set the verification status of a property
	 *
	 * @since 15.0.0
	 */
	public function setVerified(string $verified): IAccountProperty {
		$this->verified = $verified;
		return $this;
	}

	/**
	 * Get the name of a property
	 *
	 * @since 15.0.0
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get the value of a property
	 *
	 * @since 15.0.0
	 */
	public function getValue(): string {
		return $this->value;
	}

	/**
	 * Get the scope of a property
	 *
	 * @since 15.0.0
	 */
	public function getScope(): string {
		return $this->scope;
	}

	/**
	 * Get the verification status of a property
	 *
	 * @since 15.0.0
	 */
	public function getVerified(): string {
		return $this->verified;
	}

	public function setVerificationData(string $verificationData): IAccountProperty {
		$this->verificationData = $verificationData;
		return $this;
	}

	public function getVerificationData(): string {
		return $this->verificationData;
	}

	public function setLocallyVerified(string $verified): IAccountProperty {
		if (!in_array($verified, [
			IAccountManager::NOT_VERIFIED,
			IAccountManager::VERIFICATION_IN_PROGRESS,
			IAccountManager::VERIFIED,
		])) {
			throw new InvalidArgumentException('Provided verification value is invalid');
		}
		$this->locallyVerified = $verified;
		return $this;
	}

	public function getLocallyVerified(): string {
		return $this->locallyVerified;
	}
}
