<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\Direct;

use OCA\DAV\Db\Direct;
use OCA\DAV\Db\DirectMapper;
use OCA\DAV\Direct\DirectFile;
use OCA\DAV\Direct\DirectHome;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Test\TestCase;

class DirectHomeTest extends TestCase {
	private DirectMapper&MockObject $directMapper;
	private IRootFolder&MockObject $rootFolder;
	private ITimeFactory&MockObject $timeFactory;
	private IThrottler&MockObject $throttler;
	private IRequest&MockObject $request;
	private IEventDispatcher&MockObject $eventDispatcher;
	private DirectHome $directHome;

	protected function setUp(): void {
		parent::setUp();

		$this->directMapper = $this->createMock(DirectMapper::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->throttler = $this->createMock(IThrottler::class);
		$this->request = $this->createMock(IRequest::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->timeFactory->method('getTime')
			->willReturn(42);

		$this->request->method('getRemoteAddress')
			->willReturn('1.2.3.4');


		$this->directHome = new DirectHome(
			$this->rootFolder,
			$this->directMapper,
			$this->timeFactory,
			$this->throttler,
			$this->request,
			$this->eventDispatcher
		);
	}

	public function testCreateFile(): void {
		$this->expectException(Forbidden::class);

		$this->directHome->createFile('foo', 'bar');
	}

	public function testCreateDirectory(): void {
		$this->expectException(Forbidden::class);

		$this->directHome->createDirectory('foo');
	}

	public function testGetChildren(): void {
		$this->expectException(MethodNotAllowed::class);

		$this->directHome->getChildren();
	}

	public function testChildExists(): void {
		$this->assertFalse($this->directHome->childExists('foo'));
	}

	public function testDelete(): void {
		$this->expectException(Forbidden::class);

		$this->directHome->delete();
	}

	public function testGetName(): void {
		$this->assertSame('direct', $this->directHome->getName());
	}

	public function testSetName(): void {
		$this->expectException(Forbidden::class);

		$this->directHome->setName('foo');
	}

	public function testGetLastModified(): void {
		$this->assertSame(0, $this->directHome->getLastModified());
	}

	public function testGetChildValid(): void {
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

	public function testGetChildExpired(): void {
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

	public function testGetChildInvalid(): void {
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
			->method('sleepDelayOrThrowOnMax')
			->with(
				'1.2.3.4',
				'directlink'
			);

		$this->expectException(NotFound::class);

		$this->directHome->getChild('longtoken');
	}
}
