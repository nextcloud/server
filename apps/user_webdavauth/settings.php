<?php

/**
 * ownCloud - user_webdavauth
 *
 * @author Frank Karlitschek
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
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
 *
 */

OC_Util::checkAdminUser();

if($_POST) {
	// CSRF check
	OCP\JSON::callCheck();

	if(isset($_POST['webdav_url'])) {
		OC_CONFIG::setValue('user_webdavauth_url', strip_tags($_POST['webdav_url']));
	}
}

// fill template
$tmpl = new OC_Template( 'user_webdavauth', 'settings');
$tmpl->assign( 'webdav_url', OC_Config::getValue( "user_webdavauth_url" ));

return $tmpl->fetchPage();
