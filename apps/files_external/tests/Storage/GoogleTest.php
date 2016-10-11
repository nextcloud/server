<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
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

use \OCA\Files_External\Lib\Storage\Google;

/**
 * Class GoogleTest
 *
 * @group DB
 *
 * @package OCA\Files_External\Tests\Storage
 */
class GoogleTest extends \Test\Files\Storage\Storage {

	private $config;

	protected function setUp() {
		parent::setUp();

		$this->config = include('files_external/tests/config.php');
		if (!is_array($this->config) || !isset($this->config['google'])
			|| !$this->config['google']['run']
		) {
			$this->markTestSkipped('Google Drive backend not configured');
		}
		$this->instance = new Google($this->config['google']);
	}

	protected function tearDown() {
		if ($this->instance) {
			$this->instance->rmdir('/');
		}

		parent::tearDown();
	}

	public function testSameNameAsFolderWithExtension() {
		$this->assertTrue($this->instance->mkdir('testsamename'));
		$this->assertEquals(13, $this->instance->file_put_contents('testsamename.txt', 'some contents'));
		$this->assertEquals('some contents', $this->instance->file_get_contents('testsamename.txt'));
		$this->assertTrue($this->instance->is_dir('testsamename'));
		$this->assertTrue($this->instance->unlink('testsamename.txt'));
		$this->assertTrue($this->instance->rmdir('testsamename'));
	}
}
