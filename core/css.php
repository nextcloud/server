<?php
/**
 * @copyright Copyright (c) 2017 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @param string $app
 * @param string $file
 * @return bool
 */
function serveCachedCss($app, $file) {
	require __DIR__ . '/../config/config.php';

	$appData = $CONFIG['datadirectory'] . '/appdata_' . $CONFIG['instanceid'];
	$cssPath = "$appData/css/$app/$file";

	if (file_exists($cssPath)) {
		$expires = new \DateTime();
		$expires->setTimestamp(time());
		$expires->add(new \DateInterval('PT24H'));
		header('Expires: ' . $expires->format(\DateTime::RFC1123));
		header('Pragma: cache');
		header('Cache-Control: max-age=86400, must-revalidate');
		header("Content-type: text/css");
		readfile($cssPath);
		return true;
	} else {
		return false;
	}
}
