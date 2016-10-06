<?php
/**
 * @copyright 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace Test\File\SimpleFS;

use OC\Files\SimpleFS\SimpleFolder;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;

class SimpleFolderTest extends \Test\TestCase  {
	/** @var Folder|\PHPUnit_Framework_MockObject_MockObject */
	private $folder;

	/** @var SimpleFolder */
	private $simpleFolder;

	public function setUp() {
		parent::setUp();

		$this->folder = $this->createMock(Folder::class);
		$this->simpleFolder = new SimpleFolder($this->folder);
	}

	public function testGetName() {
		$this->folder->expects($this->once())
			->method('getName')
			->willReturn('myname');

		$this->assertEquals('myname', $this->simpleFolder->getName());
	}

	public function testDelete() {
		$this->folder->expects($this->once())
			->method('delete');

		$this->simpleFolder->delete();
	}

	public function dataFileExists() {
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider dataFileExists
	 * @param bool $exists
	 */
	public function testFileExists($exists) {
		$this->folder->expects($this->once())
			->method('nodeExists')
			->with($this->equalTo('file'))
			->willReturn($exists);

		$this->assertEquals($exists, $this->simpleFolder->fileExists('file'));
	}

	public function dataGetFile() {
		return [
			[File::class, false],
			[Folder::class, true],
			[Node::class, true],
		];
	}

	/**
	 * @dataProvider dataGetFile
	 * @param string $class
	 * @param bool $exception
	 */
	public function testGetFile($class, $exception) {
		$node = $this->createMock($class);

		$this->folder->expects($this->once())
			->method('get')
			->with($this->equalTo('file'))
			->willReturn($node);

		try {
			$result = $this->simpleFolder->getFile('file');
			$this->assertFalse($exception);
			$this->assertInstanceOf(ISimpleFile::class, $result);
		} catch (NotFoundException $e) {
			$this->assertTrue($exception);
		}
	}

	public function testNewFile() {
		$file = $this->createMock(File::class);

		$this->folder->expects($this->once())
			->method('newFile')
			->with($this->equalTo('file'))
			->willReturn($file);

		$result = $this->simpleFolder->newFile('file');
		$this->assertInstanceOf(ISimpleFile::class, $result);
	}

	public function testGetDirectoryListing() {
		$file = $this->createMock(File::class);
		$folder = $this->createMock(Folder::class);
		$node = $this->createMock(Node::class);

		$this->folder->expects($this->once())
			->method('getDirectoryListing')
			->willReturn([$file, $folder, $node]);

		$result = $this->simpleFolder->getDirectoryListing();

		$this->assertCount(1, $result);
		$this->assertInstanceOf(ISimpleFile::class, $result[0]);
	}

}
