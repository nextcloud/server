<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Services;

use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;

class AppConfig implements IAppConfig {
	public function __construct(
		private IConfig $config,
		/** @var \OC\AppConfig */
		private \OCP\IAppConfig $appConfig,
		private string $appName,
	) {
	}

	public function getAppKeys(): array {
		return $this->appConfig->getKeys($this->appName);
	}

	public function hasAppKey(string $key, ?bool $lazy = false): bool {
		return $this->appConfig->hasKey($this->appName, $key, $lazy);
	}

	public function isSensitive(string $key, ?bool $lazy = false): bool {
		return $this->appConfig->isSensitive($this->appName, $key, $lazy);
	}

	public function isLazy(string $key): bool {
		return $this->appConfig->isLazy($this->appName, $key);
	}

	public function getAllAppValues(string $key = '', bool $filtered = false): array {
		return $this->appConfig->getAllValues($this->appName, $key, $filtered);
	}

	public function setAppValue(string $key, string $value): void {
		/** @psalm-suppress InternalMethod */
		$this->appConfig->setValueMixed($this->appName, $key, $value);
	}

	public function setAppValueString(
		string $key,
		string $value,
		bool $lazy = false,
		bool $sensitive = false
	): bool {
		return $this->appConfig->setValueString($this->appName, $key, $value, $lazy, $sensitive);
	}

	public function setAppValueInt(
		string $key,
		int $value,
		bool $lazy = false,
		bool $sensitive = false
	): bool {
		return $this->appConfig->setValueInt($this->appName, $key, $value, $lazy, $sensitive);
	}

	public function setAppValueFloat(
		string $key,
		float $value,
		bool $lazy = false,
		bool $sensitive = false
	): bool {
		return $this->appConfig->setValueFloat($this->appName, $key, $value, $lazy, $sensitive);
	}

	public function setAppValueBool(
		string $key,
		bool $value,
		bool $lazy = false
	): bool {
		return $this->appConfig->setValueBool($this->appName, $key, $value, $lazy);
	}

	public function setAppValueArray(
		string $key,
		array $value,
		bool $lazy = false,
		bool $sensitive = false
	): bool {
		return $this->appConfig->setValueArray($this->appName, $key, $value, $lazy, $sensitive);
	}

	public function getAppValue(string $key, string $default = ''): string {
		/** @psalm-suppress InternalMethod */
		/** @psalm-suppress UndefinedInterfaceMethod */
		return $this->appConfig->getValueMixed($this->appName, $key, $default);
	}

	public function getAppValueString(string $key, string $default = '', bool $lazy = false): string {
		return $this->appConfig->getValueString($this->appName, $key, $default, $lazy);
	}

	public function getAppValueInt(string $key, int $default = 0, bool $lazy = false): int {
		return $this->appConfig->getValueInt($this->appName, $key, $default, $lazy);
	}

	public function getAppValueFloat(string $key, float $default = 0, bool $lazy = false): float {
		return $this->appConfig->getValueFloat($this->appName, $key, $default, $lazy);
	}

	public function getAppValueBool(string $key, bool $default = false, bool $lazy = false): bool {
		return $this->appConfig->getValueBool($this->appName, $key, $default, $lazy);
	}

	public function getAppValueArray(string $key, array $default = [], bool $lazy = false): array {
		return $this->appConfig->getValueArray($this->appName, $key, $default, $lazy);
	}

	public function deleteAppValue(string $key): void {
		$this->appConfig->deleteKey($this->appName, $key);
	}

	public function deleteAppValues(): void {
		$this->appConfig->deleteApp($this->appName);
	}

	public function setUserValue(string $userId, string $key, string $value, ?string $preCondition = null): void {
		$this->config->setUserValue($userId, $this->appName, $key, $value, $preCondition);
	}

	public function getUserValue(string $userId, string $key, string $default = ''): string {
		return $this->config->getUserValue($userId, $this->appName, $key, $default);
	}

	public function deleteUserValue(string $userId, string $key): void {
		$this->config->deleteUserValue($userId, $this->appName, $key);
	}
}
