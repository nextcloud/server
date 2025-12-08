<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests\Controller;

use OC\Files\SimpleFS\SimpleFile;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OCA\Theming\Controller\IconController;
use OCA\Theming\IconBuilder;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IRequest;
use Test\TestCase;

class IconControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;
	/** @var ThemingDefaults|\PHPUnit\Framework\MockObject\MockObject */
	private $themingDefaults;
	/** @var ITimeFactory */
	private $timeFactory;
	/** @var IconController|\PHPUnit\Framework\MockObject\MockObject */
	private $iconController;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IconBuilder|\PHPUnit\Framework\MockObject\MockObject */
	private $iconBuilder;
	/** @var FileAccessHelper|\PHPUnit\Framework\MockObject\MockObject */
	private $fileAccessHelper;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var ImageManager */
	private $imageManager;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->iconBuilder = $this->createMock(IconBuilder::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->fileAccessHelper = $this->createMock(FileAccessHelper::class);
		$this->appManager = $this->createMock(IAppManager::class);

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(123);

		$this->overwriteService(ITimeFactory::class, $this->timeFactory);

		$this->iconController = new IconController(
			'theming',
			$this->request,
			$this->themingDefaults,
			$this->iconBuilder,
			$this->imageManager,
			$this->fileAccessHelper,
			$this->appManager,
		);

		parent::setUp();
	}

	private function iconFileMock($filename, $data) {
		$icon = $this->getMockBuilder('OCP\Files\File')->getMock();
		$icon->expects($this->any())->method('getContent')->willReturn($data);
		$icon->expects($this->any())->method('getMimeType')->willReturn('image type');
		$icon->expects($this->any())->method('getEtag')->willReturn('my etag');
		$icon->expects($this->any())->method('getName')->willReturn('my name');
		$icon->expects($this->any())->method('getMTime')->willReturn(42);
		$icon->method('getName')->willReturn($filename);
		return new SimpleFile($icon);
	}

	public function testGetThemedIcon(): void {
		$file = $this->iconFileMock('icon-core-filetypes_folder.svg', 'filecontent');
		$this->imageManager->expects($this->once())
			->method('getCachedImage')
			->with('icon-core-filetypes_folder.svg')
			->willReturn($file);
		$expected = new FileDisplayResponse($file, Http::STATUS_OK, ['Content-Type' => 'image/svg+xml']);
		$expected->cacheFor(86400, false, true);
		$this->assertEquals($expected, $this->iconController->getThemedIcon('core', 'filetypes/folder.svg'));
	}

	public function testGetFaviconDefault(): void {
		// Test that the controller serves the static favicon from theming app
		$themingFavicon = \OC::$SERVERROOT . '/apps/theming/img/favicon.ico';
		$faviconContent = file_get_contents($themingFavicon);

		$this->fileAccessHelper->expects($this->once())
			->method('file_get_contents')
			->with($themingFavicon)
			->willReturn($faviconContent);

		$expected = new DataDisplayResponse(
			$faviconContent,
			Http::STATUS_OK,
			['Content-Type' => 'image/x-icon']
		);
		$expected->cacheFor(86400);

		$result = $this->iconController->getFavicon();
		$this->assertEquals($expected->getStatus(), $result->getStatus());
		$this->assertEquals($expected->getHeaders(), $result->getHeaders());
	}

	public function testGetTouchIconDefault(): void {
		// Test that the controller serves the static touch icon from theming app
		$themingTouchIcon = \OC::$SERVERROOT . '/apps/theming/img/favicon-touch.png';
		$touchIconContent = file_get_contents($themingTouchIcon);

		$this->fileAccessHelper->expects($this->once())
			->method('file_get_contents')
			->with($themingTouchIcon)
			->willReturn($touchIconContent);

		$expected = new DataDisplayResponse(
			$touchIconContent,
			Http::STATUS_OK,
			['Content-Type' => 'image/png']
		);
		$expected->cacheFor(86400);

		$result = $this->iconController->getTouchIcon();
		$this->assertEquals($expected->getStatus(), $result->getStatus());
		$this->assertEquals($expected->getHeaders(), $result->getHeaders());
	}
}
