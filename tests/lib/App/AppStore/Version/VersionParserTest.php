<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\App\AppStore\Version;

use OC\App\AppStore\Version\Version;
use OC\App\AppStore\Version\VersionParser;
use Test\TestCase;

class VersionParserTest extends TestCase  {
	/** @var VersionParser */
	private $versionParser;

	public function setUp() {
		parent::setUp();
		$this->versionParser = new VersionParser();
	}

	/**
	 * @return array
	 */
	public function versionProvider() {
		return [
			[
				'*',
				new Version('', ''),
			],
			[
				'<=8.1.2',
				new Version('', '8.1.2'),
			],
			[
				'<=9',
				new Version('', '9'),
			],
			[
				'>=9.3.2',
				new Version('9.3.2', ''),
			],
			[
				'>=8.1.2 <=9.3.2',
				new Version('8.1.2', '9.3.2'),
			],
			[
				'>=8.2 <=9.1',
				new Version('8.2', '9.1'),
			],
			[
				'>=9 <=11',
				new Version('9', '11'),
			],
		];
	}

	/**
	 * @dataProvider versionProvider
	 *
	 * @param string $input
	 * @param Version $expected
	 */
	public function testGetVersion($input,
								   Version $expected) {
		$this->assertEquals($expected, $this->versionParser->getVersion($input));
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Version cannot be parsed: BogusVersion
	 */
	public function testGetVersionException() {
		$this->versionParser->getVersion('BogusVersion');
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Version cannot be parsed: >=8.2 <=9.1a
	 */
	public function testGetVersionExceptionWithMultiple() {
		$this->versionParser->getVersion('>=8.2 <=9.1a');
	}
}
