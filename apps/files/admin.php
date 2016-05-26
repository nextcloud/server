<?php
/**
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Michael Göhler <somebody.here@gmx.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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

OCP\User::checkAdminUser();

$htaccessWorking=(getenv('htaccessWorking')=='true');
$upload_max_filesize = OC::$server->getIniWrapper()->getBytes('upload_max_filesize');
$post_max_size = OC::$server->getIniWrapper()->getBytes('post_max_size');
$maxUploadFilesize = OCP\Util::humanFileSize(min($upload_max_filesize, $post_max_size));
if($_POST && \OC::$server->getRequest()->passesCSRFCheck()) {
	if(isset($_POST['maxUploadSize'])) {
		if(($setMaxSize = OC_Files::setUploadLimit(OCP\Util::computerFileSize($_POST['maxUploadSize']))) !== false) {
			$maxUploadFilesize = OCP\Util::humanFileSize($setMaxSize);
		}
	}
}

$htaccessWritable=is_writable(OC::$SERVERROOT.'/.htaccess');
$userIniWritable=is_writable(OC::$SERVERROOT.'/.user.ini');

$tmpl = new OCP\Template( 'files', 'admin' );
$tmpl->assign( 'uploadChangable', ($htaccessWorking and $htaccessWritable) or $userIniWritable );
$tmpl->assign( 'uploadMaxFilesize', $maxUploadFilesize);
// max possible makes only sense on a 32 bit system
$tmpl->assign( 'displayMaxPossibleUploadSize', PHP_INT_SIZE===4);
$tmpl->assign( 'maxPossibleUploadSize', OCP\Util::humanFileSize(PHP_INT_MAX));
return $tmpl->fetchPage();
