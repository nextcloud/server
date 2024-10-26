<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests;

use OC\Files\Filesystem;
use OCA\Files_Sharing\Helper;

/**
 * Class HelperTest
 *
 * @group DB
 */
class HelperTest extends TestCase {

	/**
	 * test set and get share folder
	 */
	public function testSetGetShareFolder(): void {
		$this->assertSame('/', Helper::getShareFolder());

		Helper::setShareFolder('/Shared/Folder');

		$sharedFolder = Helper::getShareFolder();
		$this->assertSame('/Shared/Folder', Helper::getShareFolder());
		$this->assertTrue(Filesystem::is_dir($sharedFolder));

		// cleanup
		\OC::$server->getConfig()->deleteSystemValue('share_folder');
	}
}
