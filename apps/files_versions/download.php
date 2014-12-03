<?php

/**
* ownCloud - Download versions directly from the versions drop-down
*
* @author Bjoern Schiessle
* @copyright 2013 Bjoern Schiessle schiessle@owncloud.com
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

OCP\JSON::checkAppEnabled('files_versions');
OCP\JSON::checkLoggedIn();

$file = $_GET['file'];
$revision=(int)$_GET['revision'];

list($uid, $filename) = OCA\Files_Versions\Storage::getUidAndFilename($file);

$versionName = '/'.$uid.'/files_versions/'.$filename.'.v'.$revision;

$view = new OC\Files\View('/');

$ftype = $view->getMimeType('/'.$uid.'/files/'.$filename);

header('Content-Type:'.$ftype);
OCP\Response::setContentDispositionHeader(basename($filename), 'attachment');
OCP\Response::disableCaching();
header('Content-Length: '.$view->filesize($versionName));

OC_Util::obEnd();

$view->readfile($versionName);
