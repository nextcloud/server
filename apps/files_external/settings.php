<?php

/**
* ownCloud
*
* @author Michael Gapczynski
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
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

OCP\Util::addscript('files_external', 'settings');
OCP\Util::addstyle('files_external', 'settings');
$tmpl = new OCP\Template('files_external', 'settings');
$tmpl->assign('allowUserMounting', 'yes');
$tmpl->assign('isAdminPage', true);
$tmpl->assign('storage', array());
$tmpl->assign('groups', OC_Group::getGroups());
$tmpl->assign('backends', array('Amazon S3', 'FTP', 'Google Drive', 'SWIFT', 'WebDAV'));
$tmpl->assign('configurations', '');
$tmpl->assign('options', array('Encrypt', 'Version control', 'Allow sharing'));
return $tmpl->fetchPage();

?>