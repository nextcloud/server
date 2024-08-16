<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

/**
 * Class HelperTest
 *
 * @group DB
 */
class HelperTest extends TestCase {

	/**
	 * test set and get share folder
	 */
	public function testSetGetShareFolder() {
		$this->assertSame('/', \OCA\Files_Sharing\Helper::getShareFolder());

		\OCA\Files_Sharing\Helper::setShareFolder('/Shared/Folder');

		$sharedFolder = \OCA\Files_Sharing\Helper::getShareFolder();
		$this->assertSame('/Shared/Folder', \OCA\Files_Sharing\Helper::getShareFolder());
		$this->assertTrue(\OC\Files\Filesystem::is_dir($sharedFolder));

		// cleanup
		\OC::$server->getConfig()->deleteSystemValue('share_folder');
	}
}
