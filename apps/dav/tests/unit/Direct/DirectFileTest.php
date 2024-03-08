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
use Sabre\DAV\Exception\Forbidden;
use Test\TestCase;

class DirectFileTest extends TestCase {

	/** @var Direct */
	private $direct;

	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	private $rootFolder;

	/** @var Folder|\PHPUnit\Framework\MockObject\MockObject */
	private $userFolder;

	/** @var File|\PHPUnit\Framework\MockObject\MockObject */
	private $file;

	/** @var DirectFile */
	private $directFile;

	/** @var IEventDispatcher */
	private $eventDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->direct = Direct::fromParams([
			'userId' => 'directUser',
			'token' => 'directToken',
			'fileId' => 42,
		]);

		$this->rootFolder = $this->createMock(IRootFolder::class);

		$this->userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with('directUser')
			->willReturn($this->userFolder);

		$this->file = $this->createMock(File::class);
		$this->userFolder->method('getFirstNodeById')
			->with(42)
			->willReturn($this->file);

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->directFile = new DirectFile($this->direct, $this->rootFolder, $this->eventDispatcher);
	}

	public function testPut(): void {
		$this->expectException(Forbidden::class);

		$this->directFile->put('foo');
	}

	public function testGet(): void {
		$this->file->expects($this->once())
			->method('fopen')
			->with('rb');
		$this->directFile->get();
	}

	public function testGetContentType(): void {
		$this->file->method('getMimeType')
			->willReturn('direct/type');

		$this->assertSame('direct/type', $this->directFile->getContentType());
	}

	public function testGetETag(): void {
		$this->file->method('getEtag')
			->willReturn('directEtag');

		$this->assertSame('directEtag', $this->directFile->getETag());
	}

	public function testGetSize(): void {
		$this->file->method('getSize')
			->willReturn(42);

		$this->assertSame(42, $this->directFile->getSize());
	}

	public function testDelete(): void {
		$this->expectException(Forbidden::class);

		$this->directFile->delete();
	}

	public function testGetName(): void {
		$this->assertSame('directToken', $this->directFile->getName());
	}

	public function testSetName(): void {
		$this->expectException(Forbidden::class);

		$this->directFile->setName('foobar');
	}

	public function testGetLastModified(): void {
		$this->file->method('getMTime')
			->willReturn(42);

		$this->assertSame(42, $this->directFile->getLastModified());
	}
}
