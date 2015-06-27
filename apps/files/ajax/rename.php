<?php
/**
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
\OC::$server->getSession()->close();

$l10n = \OC::$server->getL10N('files');

$files = new \OCA\Files\App(
	\OC\Files\Filesystem::getView(),
	\OC::$server->getL10N('files')
);
try {
	$result = $files->rename(
		isset($_GET['dir']) ? (string)$_GET['dir'] : '',
		isset($_GET['file']) ? (string)$_GET['file'] : '',
		isset($_GET['newname']) ? (string)$_GET['newname'] : ''
	);
} catch (\Exception $e) {
	$result = [
		'success' => false,
		'data' => [
			'message' => $e->getMessage()
		]
	];
}

if($result['success'] === true){
	OCP\JSON::success(['data' => $result['data']]);
} else {
	OCP\JSON::error(['data' => $result['data']]);
}
