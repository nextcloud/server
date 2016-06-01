<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Kamil Domanski <kdomanski@kdemail.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$groups = isset($_POST['groups']) ? (array)$_POST['groups'] : null;

try {
	$app = OC_App::cleanAppId((string)$_POST['appid']);
	OC_App::enable($app, $groups);
	OC_JSON::success(['data' => ['update_required' => \OC_App::shouldUpgrade($app)]]);
} catch (Exception $e) {
	\OCP\Util::writeLog('core', $e->getMessage(), \OCP\Util::ERROR);
	OC_JSON::error(array("data" => array("message" => $e->getMessage()) ));
}
