<?php
/**
 * @author Felix Moeller <mail@felixmoeller.de>
 * @author Frank Karlitschek <frank@owncloud.org>
 * @author j-ed <juergen@eisfair.org>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

require_once OC_App::getAppPath('user_webdavauth').'/user_webdavauth.php';

OC_APP::registerAdmin('user_webdavauth', 'settings');

OC_User::registerBackend("WEBDAVAUTH");
OC_User::useBackend( "WEBDAVAUTH" );

// add settings page to navigation
$entry = array(
	'id' => "user_webdavauth_settings",
	'order'=>1,
	'href' => \OCP\Util::linkTo( "user_webdavauth", "settings.php" ),
	'name' => 'WEBDAVAUTH'
);
