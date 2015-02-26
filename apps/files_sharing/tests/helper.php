<?php
use OCA\Files_sharing\Tests\TestCase;

/**
 * ownCloud
 *
 * @author Bjoern Schiessle
 * @copyright 2014 Bjoern Schiessle <schiessle@owncloud.com>
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

class Test_Files_Sharing_Helper extends TestCase {

	/**
	 * test set and get share folder
	 */
	function testSetGetShareFolder() {
		$this->assertSame('/', \OCA\Files_Sharing\Helper::getShareFolder());

		\OCA\Files_Sharing\Helper::setShareFolder('/Shared');

		$this->assertSame('/Shared', \OCA\Files_Sharing\Helper::getShareFolder());

		// cleanup
		\OC::$server->getConfig()->deleteSystemValue('share_folder');

	}

}
