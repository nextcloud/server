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
	public function setAttribute($scope, $key, $enabled) {
		if (!\array_key_exists($scope, $this->attributes)) {
			$this->attributes[$scope] = [];
		}
		$this->attributes[$scope][$key] = $enabled;
		return $this;
	}

	/**
	 * @inheritdoc
	 */
	public function getAttribute($scope, $key) {
		if (\array_key_exists($scope, $this->attributes) &&
			\array_key_exists($key, $this->attributes[$scope])) {
			return $this->attributes[$scope][$key];
		}
		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function toArray() {
		$result = [];
		foreach ($this->attributes as $scope => $keys) {
			foreach ($keys as $key => $enabled) {
				$result[] = [
					"scope" => $scope,
					"key" => $key,
					"enabled" => $enabled
				];
			}
		}

		return $result;
	}
}
