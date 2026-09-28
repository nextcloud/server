<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Files\Conversion\IConversionManager;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class ConversionApiController
 *
 * @package OCA\Files\Controller
 */
class ConversionApiControllerTest extends TestCase {
	private string $appName = 'files';
	private ConversionApiController $conversionApiController;
	private IRequest&MockObject $request;
	private IConversionManager&MockObject $fileConversionManager;
	private IRootFolder&MockObject $rootFolder;
	private File&MockObject $file;
	private Folder&MockObject $userFolder;
	private IL10N&MockObject $l10n;
	private string $user;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->fileConversionManager = $this->createMock(IConversionManager::class);
		$this->file = $this->createMock(File::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->user = 'userid';

		$this->userFolder = $this->createMock(Folder::class);

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->rootFolder->method('getUserFolder')->with($this->user)->willReturn($this->userFolder);

		$this->conversionApiController = new ConversionApiController(
			$this->appName,
			$this->request,
			$this->fileConversionManager,
			$this->rootFolder,
			$this->l10n,
			$this->user,
		);
	}

	public function testThrowsNotFoundException(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->conversionApiController->convert(42, 'image/png');
	}

	public function testThrowsOcsException(): void {
		$this->userFolder->method('getFirstNodeById')->with(42)->willReturn($this->file);
		$this->fileConversionManager->method('convert')->willThrowException(new \Exception());

		$this->expectException(OCSException::class);
		$this->conversionApiController->convert(42, 'image/png');
	}

	public function testConvert(): void {
		$convertedFileAbsolutePath = $this->user . '/files/test.png';

		$this->userFolder->method('getFirstNodeById')->with(42)->willReturn($this->file);
		$this->userFolder->method('getRelativePath')->with($convertedFileAbsolutePath)->willReturn('/test.png');
		$this->userFolder->method('get')->with('/test.png')->willReturn($this->file);

		$this->file->method('getId')->willReturn(42);

		$this->fileConversionManager->method('convert')->with($this->file, 'image/png', null)->willReturn($convertedFileAbsolutePath);

		$actual = $this->conversionApiController->convert(42, 'image/png', null);
		$expected = new DataResponse([
			'path' => '/test.png',
			'fileId' => 42,
		], Http::STATUS_CREATED);

		$this->assertEquals($expected, $actual);
	}
}
