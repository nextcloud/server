<?php
/**
 * @copyright Copyright (c) 2016 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCA\Theming\Tests;

use OCA\Theming\ImageManager;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use OCP\IURLGenerator;
use Test\TestCase;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;

class ImageManagerTest extends TestCase {

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var IAppData|\PHPUnit_Framework_MockObject_MockObject */
	protected $appData;
	/** @var ImageManager */
	protected $imageManager;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;

	protected function setUp() {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->imageManager = new ImageManager(
			$this->config,
			$this->appData,
			$this->urlGenerator
		);
	}

	public function mockGetImage($key, $file) {
		/** @var \PHPUnit_Framework_MockObject_MockObject $folder */
		$folder = $this->createMock(ISimpleFolder::class);
		if ($file === null) {
			$folder->expects($this->once())
				->method('getFile')
				->with('logo')
				->willThrowException(new NotFoundException());
		} else {
			$folder->expects($this->once())
				->method('getFile')
				->with('logo')
				->willReturn($file);
			$this->appData->expects($this->once())
				->method('getFolder')
				->with('images')
				->willReturn($folder);
		}
	}

	public function testGetImageUrl() {
		$file = $this->createMock(ISimpleFile::class);
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->withConsecutive(
				['theming', 'cachebuster', '0'],
				['theming', 'logoMime', false]
				)
			->willReturn(0);
		$this->mockGetImage('logo', $file);
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->willReturn('url-to-image');
		$this->assertEquals('url-to-image?v=0', $this->imageManager->getImageUrl('logo'));
	}

	public function testGetImageUrlDefault() {
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->withConsecutive(
				['theming', 'cachebuster', '0'],
				['theming', 'logoMime', false]
			)
			->willReturnOnConsecutiveCalls(0, false);
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', 'logo.png')
			->willReturn('logo.png');
		$this->assertEquals('logo.png?v=0', $this->imageManager->getImageUrl('logo'));
	}

	public function testGetImageUrlAbsolute() {
		$file = $this->createMock(ISimpleFile::class);
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->withConsecutive(
				['theming', 'cachebuster', '0'],
				['theming', 'logoMime', false]
			)
			->willReturn(0);
		$this->mockGetImage('logo', $file);
		$this->urlGenerator->expects($this->at(0))
			->method('linkToRoute')
			->willReturn('url-to-image');
		$this->urlGenerator->expects($this->at(1))
			->method('getAbsoluteUrl')
			->with('url-to-image?v=0')
			->willReturn('url-to-image-absolute?v=0');
		$this->assertEquals('url-to-image-absolute?v=0', $this->imageManager->getImageUrlAbsolute('logo'));

	}

	public function testGetImage() {
		$this->config->expects($this->once())
			->method('getAppValue')->with('theming', 'logoMime', false)
			->willReturn('png');
		$file = $this->createMock(ISimpleFile::class);
		$this->mockGetImage('logo', $file);
		$this->assertEquals($file, $this->imageManager->getImage('logo'));
	}

	/**
	 * @expectedException OCP\Files\NotFoundException
	 */
	public function testGetImageUnset() {
		$this->config->expects($this->once())
			->method('getAppValue')->with('theming', 'logoMime', false)
			->willReturn(false);
		$this->imageManager->getImage('logo');
	}

	public function testGetCacheFolder() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->appData->expects($this->at(0))
			->method('getFolder')
			->with('0')
			->willReturn($folder);
		$this->assertEquals($folder, $this->imageManager->getCacheFolder());
	}
	public function testGetCacheFolderCreate() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->config->expects($this->exactly(2))
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->appData->expects($this->at(0))
			->method('getFolder')
			->willThrowException(new NotFoundException());
		$this->appData->expects($this->at(1))
			->method('newFolder')
			->with('0')
			->willReturn($folder);
		$this->appData->expects($this->at(2))
			->method('getFolder')
			->with('0')
			->willReturn($folder);
		$this->appData->expects($this->once())
			->method('getDirectoryListing')
			->willReturn([]);
		$this->assertEquals($folder, $this->imageManager->getCacheFolder());
	}

	public function testGetCachedImage() {
		$expected = $this->createMock(ISimpleFile::class);
		$folder = $this->setupCacheFolder();
		$folder->expects($this->once())
			->method('getFile')
			->with('filename')
			->willReturn($expected);
		$this->assertEquals($expected, $this->imageManager->getCachedImage('filename'));
	}

	/**
	 * @expectedException \OCP\Files\NotFoundException
	 */
	public function testGetCachedImageNotFound() {
		$folder = $this->setupCacheFolder();
		$folder->expects($this->once())
			->method('getFile')
			->with('filename')
			->will($this->throwException(new \OCP\Files\NotFoundException()));
		$image = $this->imageManager->getCachedImage('filename');
	}

	public function testSetCachedImage() {
		$folder = $this->setupCacheFolder();
		$file = $this->createMock(ISimpleFile::class);
		$folder->expects($this->once())
			->method('fileExists')
			->with('filename')
			->willReturn(true);
		$folder->expects($this->once())
			->method('getFile')
			->with('filename')
			->willReturn($file);
		$file->expects($this->once())
			->method('putContent')
			->with('filecontent');
		$this->assertEquals($file, $this->imageManager->setCachedImage('filename', 'filecontent'));
	}

	public function testSetCachedImageCreate() {
		$folder = $this->setupCacheFolder();
		$file = $this->createMock(ISimpleFile::class);
		$folder->expects($this->once())
			->method('fileExists')
			->with('filename')
			->willReturn(false);
		$folder->expects($this->once())
			->method('newFile')
			->with('filename')
			->willReturn($file);
		$file->expects($this->once())
			->method('putContent')
			->with('filecontent');
		$this->assertEquals($file, $this->imageManager->setCachedImage('filename', 'filecontent'));
	}

	private function setupCacheFolder() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
		$this->appData->expects($this->at(0))
			->method('getFolder')
			->with('0')
			->willReturn($folder);
		return $folder;
	}

	public function testCleanup() {
		$folders = [
			$this->createMock(ISimpleFolder::class),
			$this->createMock(ISimpleFolder::class),
			$this->createMock(ISimpleFolder::class)
			];
		foreach ($folders as $index=>$folder) {
			$folder->expects($this->any())
				->method('getName')
				->willReturn($index);
		}
		$folders[0]->expects($this->once())->method('delete');
		$folders[1]->expects($this->once())->method('delete');
		$folders[2]->expects($this->never())->method('delete');
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('theming','cachebuster','0')
			->willReturn('2');
		$this->appData->expects($this->once())
			->method('getDirectoryListing')
			->willReturn($folders);
		$this->appData->expects($this->once())
			->method('getFolder')
			->with('2')
			->willReturn($folders[2]);
		$this->imageManager->cleanup();
	}

}
