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

OC_Util::checkAdminUser();

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
$config = \OC::$server->getConfig();

if(isset($_POST['app']) && $_POST['app'] === 'oca-password-policy') {
	// CSRF check
	OCP\JSON::callCheck();

	foreach($appValues as $key => $default) {
		if(array_key_exists($key, $_POST)) {
			if (substr($key, -6) === '_value' && $key !== 'spv_def_special_chars_value') {
				$value = min(max(0, (int) $_POST[$key]), 255);
				$config->setAppValue('password_policy', $key, $value);
			} else {
				$config->setAppValue('password_policy', $key, strip_tags($_POST[$key]));
			}
		} else {
			$config->setAppValue('password_policy', $key, $default);
		}
	}
}

// fill template
$template = new OCP\Template('password_policy', 'admin');

foreach($appValues as $key => $default) {
	$template->assign($key, $config->getAppValue('password_policy', $key, $default));
}

return $template->fetchPage();
