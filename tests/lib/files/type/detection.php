<?php
/**
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OC\Files\Type;

use \OC\Files\Type\Detection;

class DetectionTest extends \Test\TestCase {

	public function testDetect() {
		$detection = new Detection(\OC::$server->getURLGenerator());
		$dir = \OC::$SERVERROOT.'/tests/data';

		$result = $detection->detect($dir."/");
		$expected = 'httpd/unix-directory';
		$this->assertEquals($expected, $result);

		$result = $detection->detect($dir."/data.tar.gz");
		$expected = 'application/x-gzip';
		$this->assertEquals($expected, $result);

		$result = $detection->detect($dir."/data.zip");
		$expected = 'application/zip';
		$this->assertEquals($expected, $result);

		$result = $detection->detect($dir."/testimagelarge.svg");
		$expected = 'image/svg+xml';
		$this->assertEquals($expected, $result);

		$result = $detection->detect($dir."/testimage.png");
		$expected = 'image/png';
		$this->assertEquals($expected, $result);
	}

	public function testGetSecureMimeType() {
		$detection = new Detection(\OC::$server->getURLGenerator());

		$result = $detection->getSecureMimeType('image/svg+xml');
		$expected = 'text/plain';
		$this->assertEquals($expected, $result);

		$result = $detection->getSecureMimeType('image/png');
		$expected = 'image/png';
		$this->assertEquals($expected, $result);
	}

	public function testDetectPath() {
		$detection = new Detection(\OC::$server->getURLGenerator());

		$this->assertEquals('text/plain', $detection->detectPath('foo.txt'));
		$this->assertEquals('image/png', $detection->detectPath('foo.png'));
		$this->assertEquals('image/png', $detection->detectPath('foo.bar.png'));
		$this->assertEquals('application/octet-stream', $detection->detectPath('.png'));
		$this->assertEquals('application/octet-stream', $detection->detectPath('foo'));
		$this->assertEquals('application/octet-stream', $detection->detectPath(''));
	}

	public function testDetectString() {
		if (\OC_Util::runningOnWindows()) {
			$this->markTestSkipped('[Windows] Strings have mimetype application/octet-stream on Windows');
		}

		$detection = new Detection(\OC::$server->getURLGenerator());

		$result = $detection->detectString("/data/data.tar.gz");
		$expected = 'text/plain; charset=us-ascii';
		$this->assertEquals($expected, $result);
	}

}
