<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib;

use OCP\Files\Storage\IStorage;

/**
 * Polyfill for checking dependencies using legacy Storage::checkDependencies()
 */
trait LegacyDependencyCheckPolyfill {

	/**
	 * @return class-string<IStorage>
	 */
	abstract public function getStorageClass();

	/**
	 * Check if object is valid for use
	 *
	 * @return MissingDependency[] Unsatisfied dependencies
	 */
	public function checkDependencies() {
		$ret = [];

		$result = call_user_func([$this->getStorageClass(), 'checkDependencies']);
		if ($result !== true) {
			if (!is_array($result)) {
				$result = [$result];
			}
			foreach ($result as $key => $value) {
				if (!($value instanceof MissingDependency)) {
					$module = null;
					$message = null;
					if (is_numeric($key)) {
						$module = $value;
					} else {
						$module = $key;
						$message = $value;
					}
					$value = new MissingDependency($module);
					$value->setMessage($message);
				}
				$ret[] = $value;
			}
		}

		return $ret;
	}
}
