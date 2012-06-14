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
 *
 */

// Init owncloud
 

// Check if we are a user
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('contacts');

$tmp_path = strip_tags($_GET['tmp_path']);
$requesttoken = strip_tags($_GET['requesttoken']);
$id = $_GET['id'];
OCP\Util::writeLog('contacts','ajax/cropphoto.php: tmp_path: '.$tmp_path.', exists: '.file_exists($tmp_path), OCP\Util::DEBUG);
$tmpl = new OCP\Template("contacts", "part.cropphoto");
$tmpl->assign('tmp_path', $tmp_path);
$tmpl->assign('id', $id);
$tmpl->assign('requesttoken', $requesttoken);
$page = $tmpl->fetchPage();

OCP\JSON::success(array('data' => array( 'page' => $page )));
