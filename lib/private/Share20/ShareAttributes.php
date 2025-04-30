<?php

/**
 * SPDX-FileCopyrightText: 2023-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2019-2022 ownCloud GmbH
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Share20;

use OCP\Share\IAttributes;

class ShareAttributes implements IAttributes {
	/** @var array */
	private $attributes;

	public function __construct() {
		$this->attributes = [];
	}

	/**
	 * @inheritdoc
	 */
	public function setAttribute(string $scope, string $key, mixed $value): IAttributes {
		if (!\array_key_exists($scope, $this->attributes)) {
			$this->attributes[$scope] = [];
		}
		$this->attributes[$scope][$key] = $value;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getAttribute(string $scope, string $key): mixed {
		if (\array_key_exists($scope, $this->attributes) &&
			\array_key_exists($key, $this->attributes[$scope])) {
			return $this->attributes[$scope][$key];
		}
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function toArray(): array {
		$result = [];
		foreach ($this->attributes as $scope => $keys) {
			foreach ($keys as $key => $value) {
				$result[] = [
					'scope' => $scope,
					'key' => $key,
					'value' => $value,
				];
			}
		}

		return $result;
	}
}
