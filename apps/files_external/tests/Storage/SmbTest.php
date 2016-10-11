<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Files_External\Tests\Storage;

use \OCA\Files_External\Lib\Storage\SMB;

/**
 * Class SmbTest
 *
 * @group DB
 *
 * @package OCA\Files_External\Tests\Storage
 */
class SmbTest extends \Test\Files\Storage\Storage {

	protected function setUp() {
		parent::setUp();

		$id = $this->getUniqueID();
		$config = include('files_external/tests/config.smb.php');
		if (!is_array($config) or !$config['run']) {
			$this->markTestSkipped('Samba backend not configured');
		}
		if (substr($config['root'], -1, 1) != '/') {
			$config['root'] .= '/';
		}
		$config['root'] .= $id; //make sure we have an new empty folder to work in
		$this->instance = new SMB($config);
		$this->instance->mkdir('/');
	}

	protected function tearDown() {
		if ($this->instance) {
			$this->instance->rmdir('');
		}

		parent::tearDown();
	}

	public function directoryProvider() {
		// doesn't support leading/trailing spaces
		return array(array('folder'));
	}

	public function testRenameWithSpaces() {
		$this->instance->mkdir('with spaces');
		$result = $this->instance->rename('with spaces', 'foo bar');
		$this->assertTrue($result);
		$this->assertTrue($this->instance->is_dir('foo bar'));
	}

	public function testStorageId() {
		$this->instance = new SMB([
			'host' => 'testhost',
			'user' => 'testuser',
			'password' => 'somepass',
			'share' => 'someshare',
			'root' => 'someroot',
		]);
		$this->assertEquals('smb::testuser@testhost//someshare//someroot/', $this->instance->getId());
		$this->instance = null;
	}
}
