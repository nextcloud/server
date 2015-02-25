<?php
/**
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Lukas Reschke <lukas@owncloud.com>
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
// This file is just used to redirect the legacy sharing URLs (< ownCloud 8) to the new ones

$urlGenerator = new \OC\URLGenerator(\OC::$server->getConfig());
$token = isset($_GET['t']) ? $_GET['t'] : '';
$route = isset($_GET['download']) ? 'files_sharing.sharecontroller.downloadShare' : 'files_sharing.sharecontroller.showShare';

if($token !== '') {
	OC_Response::redirect($urlGenerator->linkToRoute($route, array('token' => $token)));
} else {
	header('HTTP/1.0 404 Not Found');
	$tmpl = new OCP\Template('', '404', 'guest');
	print_unescaped($tmpl->fetchPage());
}
