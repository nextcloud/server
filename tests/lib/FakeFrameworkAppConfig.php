<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OCP\AppFramework\Services\IAppConfig;

/**
 * This is a fake AppConfig class used for testing purposes.
 * It allows to test the AppConfig class without relying on the actual database or cache implementations.
 */
class FakeFrameworkAppConfig implements IAppConfig {

	public function __construct(
		private readonly string $appId,
		private readonly FakeAppConfig $appConfig = new FakeAppConfig(),
	) {
	}

	public function getAppKeys(): array {
		return $this->appConfig->getKeys($this->appId);
	}

	public function hasAppKey(string $key, ?bool $lazy = false): bool {
		return $this->appConfig->hasKey($this->appId, $key, $lazy);
	}

	public function isSensitive(string $key, ?bool $lazy = false): bool {
		return $this->appConfig->isSensitive($this->appId, $key, $lazy);
	}

	public function isLazy(string $key): bool {
		return $this->appConfig->isLazy($this->appId, $key);
	}

	public function getAllAppValues(string $key = '', bool $filtered = false): array {
		return $this->appConfig->getAllValues($this->appId, $key, $filtered);
	}

	public function setAppValue(string $key, string $value): void {
		throw new \Exception('Deprecated. Use typed method.');
	}

	public function setAppValueString(string $key, string $value, bool $lazy = false, bool $sensitive = false): bool {
		return $this->appConfig->setValueString($this->appId, $key, $value, $lazy, $sensitive);
	}

	public function setAppValueInt(string $key, int $value, bool $lazy = false, bool $sensitive = false): bool {
		return $this->appConfig->setValueInt($this->appId, $key, $value, $lazy, $sensitive);
	}

	public function setAppValueFloat(string $key, float $value, bool $lazy = false, bool $sensitive = false): bool {
		return $this->appConfig->setValueFloat($this->appId, $key, $value, $lazy, $sensitive);
	}

	public function setAppValueBool(string $key, bool $value, bool $lazy = false): bool {
		return $this->appConfig->setValueBool($this->appId, $key, $value, $lazy);
	}

	public function setAppValueArray(string $key, array $value, bool $lazy = false, bool $sensitive = false): bool {
		return $this->appConfig->setValueArray($this->appId, $key, $value, $lazy, $sensitive);
	}

	public function getAppValue(string $key, string $default = ''): string {
		throw new \Exception('Deprecated. Use typed method.');
	}

	public function getAppValueString(string $key, string $default = '', bool $lazy = false): string {
		return $this->appConfig->getValueString($this->appId, $key, $default, $lazy);
	}

	public function getAppValueInt(string $key, int $default = 0, bool $lazy = false): int {
		return $this->appConfig->getValueInt($this->appId, $key, $default, $lazy);
	}

	public function getAppValueFloat(string $key, float $default = 0, bool $lazy = false): float {
		return $this->appConfig->getValueFloat($this->appId, $key, $default, $lazy);
	}

	public function getAppValueBool(string $key, bool $default = false, bool $lazy = false): bool {
		return $this->appConfig->getValueBool($this->appId, $key, $default, $lazy);
	}

	public function getAppValueArray(string $key, array $default = [], bool $lazy = false): array {
		return $this->appConfig->getValueArray($this->appId, $key, $default, $lazy);
	}

	public function deleteAppValue(string $key): void {
		$this->appConfig->deleteKey($this->appId, $key);
	}

	public function deleteAppValues(): void {
		$this->appConfig->deleteApp($this->appId);
	}

	public function setUserValue(string $userId, string $key, string $value, ?string $preCondition = null): void {
		throw new \Exception('Fake method not implemented.');
	}

	public function getUserValue(string $userId, string $key, string $default = ''): string {
		throw new \Exception('Fake method not implemented.');
	}

	public function deleteUserValue(string $userId, string $key): void {
		throw new \Exception('Fake method not implemented.');
	}
}

