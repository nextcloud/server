<?php
/**

 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\password_policy;

use Exception;

class HooksHandler {

	public static function verifyPassword($params) {

		$configValues = self::loadConfiguration();

		$engine = new Engine($configValues);

		try {
			$engine->verifyPassword($params['password']);
			$params['accepted'] = true;
			$params['message'] = '';
		} catch (Exception $ex) {
			$params['accepted'] = false;
			$params['message'] = $ex->getMessage();
		}
	}

	public static function applyLinkExpiry($params) {
		$configValues = self::loadConfiguration();

		if (isset($params['shareWith'])) {
			if ($configValues['spv_expiration_password_checked'] === 'on') {
				$days = $configValues['spv_expiration_password_value'];
				$date = Date('d-m-Y', strtotime("+$days days"));
				\OCP\Share::setExpirationDate($params['itemType'], $params['itemSource'], $date);
			}
		} else {
			if ($configValues['spv_expiration_nopassword_checked'] === 'on') {
				$days = $configValues['spv_expiration_nopassword_value'];
				$date = Date('d-m-Y', strtotime("+$days days"));
				\OCP\Share::setExpirationDate($params['itemType'], $params['itemSource'], $date);
			}
		}
	}

	public static function updateLinkExpiry($params) {
		$configValues = self::loadConfiguration();

		$data = null;

		if ($params['passwordSet'] === true) {
			if ($configValues['spv_expiration_password_checked'] === 'on') {
				$days = $configValues['spv_expiration_password_value'];
				$date = new \DateTime();
				$date->setTime(0,0,0);
				$date->add(new \DateInterval('P'.$days.'D'));
			}
		} else {
			if ($configValues['spv_expiration_nopassword_checked'] === 'on') {
				$days = $configValues['spv_expiration_nopassword_value'];
				$date = new \DateTime();
				$date->setTime(0,0,0);
				$date->add(new \DateInterval('P'.$days.'D'));
			}
		}

		// $date is the max expiration date
		if ($date !== null && ($date < $params['expirationDate'] || $params['expirationDate'] === null)) {
			$params['expirationDate'] = $date;
		}
	}

	/**
	 * @return array
	 */
	private static function loadConfiguration() {
		$appValues = [
			'spv_min_chars_checked' => false,
			'spv_min_chars_value' => 8,
			'spv_uppercase_checked' => false,
			'spv_uppercase_value' => 1,
			'spv_numbers_checked' => false,
			'spv_numbers_value' => 1,
			'spv_special_chars_checked' => false,
			'spv_special_chars_value' => 1,
			'spv_def_special_chars_checked' => false,
			'spv_def_special_chars_value' => '#!',
			'spv_expiration_password_checked' => false,
			'spv_expiration_password_value' => 7,
			'spv_expiration_nopassword_checked' => false,
			'spv_expiration_nopassword_value' => 7,
		];

		$configValues = [];
		foreach ($appValues as $key => $default) {
			$configValues[$key] = \OC::$server->getConfig()->getAppValue('password_policy', $key, $default);
		}
		return $configValues;
	}
}
