<?php

declare(strict_types=1);

/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\Unit\Direct;

use OCA\DAV\Db\Direct;
use OCA\DAV\Direct\DirectFile;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\Forbidden;
use Test\TestCase;

class DirectFileTest extends TestCase {

	/** @var File|MockObject */
	private $file;

	/** @var DirectFile */
	private $directFile;

	protected function setUp(): void {
		parent::setUp();

		$direct = Direct::fromParams([
			'userId' => 'directUser',
			'token' => 'directToken',
			'fileId' => 42,
		]);

		$rootFolder = $this->createMock(IRootFolder::class);

		$userFolder = $this->createMock(Folder::class);
		$rootFolder->method('getUserFolder')
			->with('directUser')
			->willReturn($userFolder);

		$this->file = $this->createMock(File::class);
		$userFolder->method('getById')
			->with(42)
			->willReturn([$this->file]);

		$eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->directFile = new DirectFile($direct, $rootFolder, $eventDispatcher);
	}

	public function testPut() {
		$this->expectException(Forbidden::class);

		$this->directFile->put('foo');
	}

	public function testGet() {
		$this->file->expects($this->once())
			->method('fopen')
			->with('rb');
		$this->directFile->get();
	}

	public function testGetContentType() {
		$this->file->method('getMimeType')
			->willReturn('direct/type');

		$this->assertSame('direct/type', $this->directFile->getContentType());
	}

	public function testGetETag() {
		$this->file->method('getEtag')
			->willReturn('directEtag');

		$this->assertSame('directEtag', $this->directFile->getETag());
	}

	public function testGetSize() {
		$this->file->method('getSize')
			->willReturn(42);

		$this->assertSame(42, $this->directFile->getSize());
	}

	public function testDelete() {
		$this->expectException(Forbidden::class);

		$this->directFile->delete();
	}

	public function testGetName() {
		$this->assertSame('directToken', $this->directFile->getName());
	}

	public function testSetName() {
		$this->expectException(Forbidden::class);

		$this->directFile->setName('foobar');
	}

	public function testGetLastModified() {
		$this->file->method('getMTime')
			->willReturn(42);

		$this->assertSame(42, $this->directFile->getLastModified());
	}
}
