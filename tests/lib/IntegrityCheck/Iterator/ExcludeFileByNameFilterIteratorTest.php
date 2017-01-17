<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

namespace Test\IntegrityCheck\Iterator;

use \OC\IntegrityCheck\Iterator\ExcludeFileByNameFilterIterator;
use Test\TestCase;

class ExcludeFileByNameFilterIteratorTest extends TestCase {
	/** @var ExcludeFileByNameFilterIterator */
	protected $filter;

	public function setUp() {
		parent::setUp();
		$this->filter = $this->getMockBuilder(ExcludeFileByNameFilterIterator::class)
			->disableOriginalConstructor()
			->getMock()
		;

	}

	public function fileNameProvider(){
		return [
			['a file', true],
			['Thumbs.db', false],
			['another file', true],
			['.directory', false],
			['.webapp-owncloud-obee', false],
			['wx.webapp-owncloud-obee', true],
		];
	}

	/**
	 * @dataProvider fileNameProvider
	 * @param string $fileName
	 * @param bool $expectedResult
	 */
	public function testAcceptForFiles($fileName, $expectedResult){
		$iteratorMock = $this->createMock(\DirectoryIterator::class);
		$iteratorMock->method('getFilename')
			->will($this->returnValue($fileName))
		;

		$this->filter->method('isDir')
			->will($this->returnValue(false));
		$this->filter->method('current')
			->will($this->returnValue($iteratorMock))
		;

		$actualResult = $this->filter->accept();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @dataProvider fileNameProvider
	 * @param string $fileName
	 * @param bool $fakeExpectedResult
	 */
	public function testAcceptForDirs($fileName, $fakeExpectedResult){
		$iteratorMock = $this->createMock(\DirectoryIterator::class);
		$iteratorMock->method('getFilename')
			->will($this->returnValue($fileName))
		;

		$this->filter->method('isDir')
			->will($this->returnValue(true));
		$this->filter->method('current')
			->will($this->returnValue($iteratorMock))
		;

		$actualResult = $this->filter->accept();
		$this->assertFalse($actualResult);
	}
}
