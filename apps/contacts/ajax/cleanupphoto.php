<?php
/**
 * ownCloud - Addressbook
 *
 * @author Thomas Tanghus
 * @copyright 2012 Thomas Tanghus <thomas@tanghus.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */
// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

$tmp_path = isset($_POST['tmp_path']) ? $_POST['tmp_path'] : '';

// give some time to save the photo
sleep(5);

if($tmp_path != '' && file_exists($tmp_path) && !is_dir($tmp_path) && dirname($tmp_path)==get_temp_dir()) {
	unlink($tmp_path);
	OCP\JSON::success();
	exit();
} else {
	error_log('Couldn\'t find: '.$tmp_path);
	OCP\JSON::error();
}
