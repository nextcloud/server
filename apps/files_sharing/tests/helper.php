<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
use OCA\Files_sharing\Tests\TestCase;

/**
 * Class Test_Files_Sharing_Helper
 *
 * @group DB
 */
class Test_Files_Sharing_Helper extends TestCase {

	/**
	 * test set and get share folder
	 */
	function testSetGetShareFolder() {
		$this->assertSame('/', \OCA\Files_Sharing\Helper::getShareFolder());

		\OCA\Files_Sharing\Helper::setShareFolder('/Shared/Folder');

		$sharedFolder = \OCA\Files_Sharing\Helper::getShareFolder();
		$this->assertSame('/Shared/Folder', \OCA\Files_Sharing\Helper::getShareFolder());
		$this->assertTrue(\OC\Files\Filesystem::is_dir($sharedFolder));

		// cleanup
		\OC::$server->getConfig()->deleteSystemValue('share_folder');

	}

}
