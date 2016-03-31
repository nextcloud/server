<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
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

namespace OCA\DAV\Tests\Unit\Connector\Sabre\RequestTest;

use OC\Files\View;
use Test\Traits\EncryptionTrait;

/**
 * Class EncryptionUploadTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\Connector\Sabre\RequestTest
 */
class PartFileInRootUpload extends UploadTest {
	protected function setUp() {
		$config = \OC::$server->getConfig();
		$mockConfig = $this->getMock('\OCP\IConfig');
		$mockConfig->expects($this->any())
			->method('getSystemValue')
			->will($this->returnCallback(function ($key, $default) use ($config) {
				if ($key === 'part_file_in_storage') {
					return false;
				} else {
					return $config->getSystemValue($key, $default);
				}
			}));
		$this->overwriteService('AllConfig', $mockConfig);
		parent::setUp();
	}

	protected function tearDown() {
		$this->restoreService('AllConfig');
		return parent::tearDown();
	}
}
