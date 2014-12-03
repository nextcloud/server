<?php

/**
 * ownCloud
 *
 * @author Michael Gapczynski
 * @author Christian Berendt 
 * @copyright 2012 Michael Gapczynski mtgap@owncloud.com
 * @copyright 2013 Christian Berendt berendt@b1-systems.de
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
 */

namespace Test\Files\Storage;

class AmazonS3 extends Storage {

	private $config;

	protected function setUp() {
		parent::setUp();

		$this->config = include('files_external/tests/config.php');
		if ( ! is_array($this->config) or ! isset($this->config['amazons3']) or ! $this->config['amazons3']['run']) {
			$this->markTestSkipped('AmazonS3 backend not configured');
		}
		$this->instance = new \OC\Files\Storage\AmazonS3($this->config['amazons3']);
	}

	protected function tearDown() {
		if ($this->instance) {
			$this->instance->rmdir('');
		}

		parent::tearDown();
	}

	public function testStat() {
		$this->markTestSkipped('S3 doesn\'t update the parents folder mtime');
	}
}
