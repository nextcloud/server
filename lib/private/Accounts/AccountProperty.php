<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OC\Accounts;

use InvalidArgumentException;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;

class AccountProperty implements IAccountProperty {
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
		$newScope = $this->mapScopeToV2($scope);
		if (!in_array($newScope, [
			IAccountManager::SCOPE_LOCAL,
			IAccountManager::SCOPE_FEDERATED,
			IAccountManager::SCOPE_PRIVATE,
			IAccountManager::SCOPE_PUBLISHED
		])) {
			throw new InvalidArgumentException('Invalid scope');
		}
		$this->scope = $newScope;
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

	public static function mapScopeToV2(string $scope): string {
		if (str_starts_with($scope, 'v2-')) {
			return $scope;
		}

		return match ($scope) {
			IAccountManager::VISIBILITY_PRIVATE, '' => IAccountManager::SCOPE_LOCAL,
			IAccountManager::VISIBILITY_CONTACTS_ONLY => IAccountManager::SCOPE_FEDERATED,
			IAccountManager::VISIBILITY_PUBLIC => IAccountManager::SCOPE_PUBLISHED,
			default => $scope,
		};
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
