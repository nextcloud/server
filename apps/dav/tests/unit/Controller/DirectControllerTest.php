<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\Unit\DAV\Controller;

use OCA\DAV\Controller\DirectController;
use OCA\DAV\Db\Direct;
use OCA\DAV\Db\DirectMapper;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUrlGenerator;
use OCP\Security\ISecureRandom;
use Test\TestCase;

class DirectControllerTest extends TestCase {

	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	private $rootFolder;

	/** @var DirectMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $directMapper;

	/** @var ISecureRandom|\PHPUnit\Framework\MockObject\MockObject */
	private $random;

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $timeFactory;

	/** @var IUrlGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;

	/** @var IEventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
	private $eventDispatcher;

	private DirectController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->directMapper = $this->createMock(DirectMapper::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->urlGenerator = $this->createMock(IUrlGenerator::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->controller = new DirectController(
			'dav',
			$this->createMock(IRequest::class),
			$this->rootFolder,
			'awesomeUser',
			$this->directMapper,
			$this->random,
			$this->timeFactory,
			$this->urlGenerator,
			$this->eventDispatcher
		);
	}

	public function testGetUrlNonExistingFileId(): void {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with('awesomeUser')
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(101)
			->willReturn([]);

		$this->expectException(OCSNotFoundException::class);
		$this->controller->getUrl(101);
	}

	public function testGetUrlForFolder(): void {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with('awesomeUser')
			->willReturn($userFolder);

		$folder = $this->createMock(Folder::class);

		$userFolder->method('getFirstNodeById')
			->with(101)
			->willReturn($folder);

		$this->expectException(OCSBadRequestException::class);
		$this->controller->getUrl(101);
	}

	public function testGetUrlValid(): void {
		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with('awesomeUser')
			->willReturn($userFolder);

		$file = $this->createMock(File::class);

		$this->timeFactory->method('getTime')
			->willReturn(42);

		$userFolder->method('getFirstNodeById')
			->with(101)
			->willReturn($file);

		$userFolder->method('getRelativePath')
			->willReturn('/path');

		$this->random->method('generate')
			->with(
				60,
				ISecureRandom::CHAR_ALPHANUMERIC
			)->willReturn('superduperlongtoken');

		$this->directMapper->expects($this->once())
			->method('insert')
			->willReturnCallback(function (Direct $direct) {
				$this->assertSame('awesomeUser', $direct->getUserId());
				$this->assertSame(101, $direct->getFileId());
				$this->assertSame('superduperlongtoken', $direct->getToken());
				$this->assertSame(42 + 60 * 60 * 8, $direct->getExpiration());

				return $direct;
			});

		$this->urlGenerator->method('getAbsoluteURL')
			->willReturnCallback(function (string $url) {
				return 'https://my.nextcloud/' . $url;
			});

		$result = $this->controller->getUrl(101);

		$this->assertInstanceOf(DataResponse::class, $result);
		$this->assertSame([
			'url' => 'https://my.nextcloud/remote.php/direct/superduperlongtoken',
		], $result->getData());
	}
}
