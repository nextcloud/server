<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
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

use \OCA\Files_External\Lib\Storage\Dropbox;

/**
 * Class DropboxTest
 *
 * @group DB
 *
 * @package OCA\Files_External\Tests\Storage
 */
class DropboxTest extends \Test\Files\Storage\Storage {
	private $config;

	protected function setUp() {
		parent::setUp();

		$id = $this->getUniqueID();
		$this->config = include('files_external/tests/config.php');
		if ( ! is_array($this->config) or ! isset($this->config['dropbox']) or ! $this->config['dropbox']['run']) {
			$this->markTestSkipped('Dropbox backend not configured');
		}
		$this->config['dropbox']['root'] .= '/' . $id; //make sure we have an new empty folder to work in
		$this->instance = new Dropbox($this->config['dropbox']);
	}

	protected function tearDown() {
		if ($this->instance) {
			$this->instance->unlink('/');
		}

		parent::tearDown();
	}

	public function directoryProvider() {
		// doesn't support leading/trailing spaces
		return array(array('folder'));
	}

	public function testDropboxTouchReturnValue() {
		$this->assertFalse($this->instance->file_exists('foo'));

		// true because succeeded
		$this->assertTrue($this->instance->touch('foo'));
		$this->assertTrue($this->instance->file_exists('foo'));

		// false because not supported
		$this->assertFalse($this->instance->touch('foo'));
	}
}
