<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace Test\Files\Storage;

class AmazonS3 extends Storage {

	private $config;

	protected function setUp() {
		parent::setUp();

		$this->config = include('files_external/tests/config.amazons3.php');
		if ( ! is_array($this->config) or ! $this->config['run']) {
			$this->markTestSkipped('AmazonS3 backend not configured');
		}
		$this->instance = new \OC\Files\Storage\AmazonS3($this->config);
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
