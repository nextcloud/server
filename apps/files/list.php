<?php

/**
 * ownCloud - Files list
 *
 * @author Vincent Petry
 * @copyright 2014 Vincent Petry <pvince81@owncloud.com>
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


// Check if we are a user
OCP\User::checkLoggedIn();

// dummy, will be refreshed with an ajax call
$dir = '/';

// information about storage capacities
// FIXME: storage info
/*
$storageInfo=OC_Helper::getStorageInfo($dir, $dirInfo);
$publicUploadEnabled = $config->getAppValue('core', 'shareapi_allow_public_upload', 'yes');
$uploadLimit=OCP\Util::uploadLimit();
$maxUploadFilesize=OCP\Util::maxUploadFilesize($dir, $freeSpace);
$freeSpace=$storageInfo['free'];
*/

$tmpl = new OCP\Template('files', 'list', '');
$tmpl->assign('usedSpacePercent', (int)$storageInfo['relative']);
$tmpl->assign('uploadMaxFilesize', $maxUploadFilesize); // minimium of freeSpace and uploadLimit
$tmpl->assign('uploadMaxHumanFilesize', OCP\Util::humanFileSize($maxUploadFilesize));
$tmpl->assign('uploadLimit', $uploadLimit); // PHP upload limit
$tmpl->assign('freeSpace', $freeSpace);
$tmpl->assign('publicUploadEnabled', $publicUploadEnabled);
$tmpl->assign('allowZipDownload', intval(OCP\Config::getSystemValue('allowZipDownload', true)));
$tmpl->printPage();

