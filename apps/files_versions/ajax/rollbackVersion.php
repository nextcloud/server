<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
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
OCP\JSON::checkAppEnabled('files_versions');
OCP\JSON::callCheck();

$file = (string)$_GET['file'];
$revision=(int)$_GET['revision'];

if(OCA\Files_Versions\Storage::rollback( $file, $revision )) {
	OCP\JSON::success(array("data" => array( "revision" => $revision, "file" => $file )));
}else{
	$l = \OC::$server->getL10N('files_versions');
	OCP\JSON::error(array("data" => array( "message" => $l->t("Could not revert: %s", array($file) ))));
}
