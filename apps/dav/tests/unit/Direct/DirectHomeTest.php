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

use OC\Security\Bruteforce\Throttler;
use OCA\DAV\Db\Direct;
use OCA\DAV\Db\DirectMapper;
use OCA\DAV\Direct\DirectFile;
use OCA\DAV\Direct\DirectHome;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

class DirectHomeTest extends TestCase {

	/** @var DirectMapper|MockObject */
	private $directMapper;

	/** @var Throttler|MockObject */
	private $throttler;

	/** @var DirectHome */
	private $directHome;

	protected function setUp(): void {
		parent::setUp();

		$this->directMapper = $this->createMock(DirectMapper::class);
		$rootFolder = $this->createMock(IRootFolder::class);
		$timeFactory = $this->createMock(ITimeFactory::class);
		$this->throttler = $this->createMock(Throttler::class);
		$request = $this->createMock(IRequest::class);
		$eventDispatcher = $this->createMock(IEventDispatcher::class);

		$timeFactory->method('getTime')
			->willReturn(42);

		$request->method('getRemoteAddress')
			->willReturn('1.2.3.4');


		$this->directHome = new DirectHome(
			$rootFolder,
			$this->directMapper,
			$timeFactory,
			$this->throttler,
			$request,
			$eventDispatcher
		);
	}

	public function testCreateFile() {
		$this->expectException(Forbidden::class);

		$this->directHome->createFile('foo', 'bar');
	}

	public function testCreateDirectory() {
		$this->expectException(Forbidden::class);

		$this->directHome->createDirectory('foo');
	}

	public function testGetChildren() {
		$this->expectException(MethodNotAllowed::class);

		$this->directHome->getChildren();
	}

	public function testChildExists() {
		$this->assertFalse($this->directHome->childExists('foo'));
	}

	public function testDelete() {
		$this->expectException(Forbidden::class);

		$this->directHome->delete();
	}

	public function testGetName() {
		$this->assertSame('direct', $this->directHome->getName());
	}

	public function testSetName() {
		$this->expectException(Forbidden::class);

		$this->directHome->setName('foo');
	}

	public function testGetLastModified() {
		$this->assertSame(0, $this->directHome->getLastModified());
	}

	/**
	 * @throws NotFound
	 */
	public function testGetChildValid() {
		$direct = Direct::fromParams([
			'expiration' => 100,
		]);

		$this->directMapper->method('getByToken')
			->with('longtoken')
			->willReturn($direct);

		$this->throttler->expects($this->never())
			->method($this->anything());

		$result = $this->directHome->getChild('longtoken');
		$this->assertInstanceOf(DirectFile::class, $result);
	}

	public function testGetChildExpired() {
		$direct = Direct::fromParams([
			'expiration' => 41,
		]);

		$this->directMapper->method('getByToken')
			->with('longtoken')
			->willReturn($direct);

		$this->throttler->expects($this->never())
			->method($this->anything());

		$this->expectException(NotFound::class);

		$this->directHome->getChild('longtoken');
	}

	public function testGetChildInvalid() {
		$this->directMapper->method('getByToken')
			->with('longtoken')
			->willThrowException(new DoesNotExistException('not found'));

		$this->throttler->expects($this->once())
			->method('registerAttempt')
			->with(
				'directlink',
				'1.2.3.4'
			);
		$this->throttler->expects($this->once())
			->method('sleepDelay')
			->with(
				'1.2.3.4',
				'directlink'
			);

		$this->expectException(NotFound::class);

		$this->directHome->getChild('longtoken');
	}
}
