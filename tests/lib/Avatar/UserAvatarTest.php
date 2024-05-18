<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Avatar;

use OC\Files\SimpleFS\SimpleFolder;
use OC\User\User;
use OCP\Files\File;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

class UserAvatarTest extends \Test\TestCase {
	/** @var SimpleFolder | \PHPUnit\Framework\MockObject\MockObject */
	private $folder;

	/** @var \OC\Avatar\UserAvatar */
	private $avatar;

	/** @var \OC\User\User | \PHPUnit\Framework\MockObject\MockObject $user */
	private $user;

	/** @var \OCP\IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;

	protected function setUp(): void {
		parent::setUp();

		$this->folder = $this->createMock(SimpleFolder::class);
		// abcdefghi is a convenient name that our algorithm convert to our nextcloud blue 0082c9
		$this->user = $this->getUserWithDisplayName('abcdefghi');
		$this->config = $this->createMock(IConfig::class);

		$this->avatar = $this->getUserAvatar($this->user);
	}

	public function avatarTextData() {
		return [
			['', '?'],
			['matchish', 'M'],
			['Firstname Lastname', 'FL'],
			['Firstname Lastname Rest', 'FL'],
		];
	}

	public function testGetNoAvatar() {
		$file = $this->createMock(ISimpleFile::class);
		$this->folder->method('newFile')
			->willReturn($file);

		$this->folder->method('getFile')
			->willReturnCallback(function (string $path) {
				if ($path === 'avatar.64.png') {
					throw new NotFoundException();
				}
			});
		$this->folder->method('fileExists')
			->willReturnCallback(function ($path) {
				if ($path === 'generated') {
					return true;
				}
				return false;
			});

		$data = null;
		$file->method('putContent')
			->with($this->callback(function ($d) use (&$data) {
				$data = $d;
				return true;
			}));

		$file->method('getContent')
			->willReturnCallback(function () use (&$data) {
				return $data;
			});

		$result = $this->avatar->get();
		$this->assertTrue($result->valid());
	}

	public function testGetAvatarSizeMatch() {
		$this->folder->method('fileExists')
			->willReturnMap([
				['avatar.jpg', true],
				['avatar.128.jpg', true],
				['generated', false],
			]);

		$expected = new \OC_Image();
		$expected->loadFromFile(\OC::$SERVERROOT . '/tests/data/testavatar.png');

		$file = $this->createMock(ISimpleFile::class);
		$file->method('getContent')->willReturn($expected->data());
		$this->folder->method('getFile')->with('avatar.128.jpg')->willReturn($file);

		$this->assertEquals($expected->data(), $this->avatar->get(128)->data());
	}

	public function testGetAvatarSizeMinusOne() {
		$this->folder->method('fileExists')
			->willReturnMap([
				['avatar.jpg', true],
				['generated', false],
			]);

		$expected = new \OC_Image();
		$expected->loadFromFile(\OC::$SERVERROOT . '/tests/data/testavatar.png');

		$file = $this->createMock(ISimpleFile::class);
		$file->method('getContent')->willReturn($expected->data());
		$this->folder->method('getFile')->with('avatar.jpg')->willReturn($file);

		$this->assertEquals($expected->data(), $this->avatar->get(-1)->data());
	}

	public function testGetAvatarNoSizeMatch() {
		$this->folder->method('fileExists')
			->willReturnMap([
				['avatar.jpg', false],
				['avatar.png', true],
				['avatar.32.png', false],
				['generated', false],
			]);

		$expected = new \OC_Image();
		$expected->loadFromFile(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$expected2 = new \OC_Image();
		$expected2->loadFromFile(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$expected2->resize(32);

		$file = $this->createMock(ISimpleFile::class);
		$file->method('getContent')->willReturn($expected->data());

		$this->folder->method('getFile')
			->willReturnCallback(
				function ($path) use ($file) {
					if ($path === 'avatar.png') {
						return $file;
					} else {
						throw new \OCP\Files\NotFoundException;
					}
				}
			);

		$newFile = $this->createMock(ISimpleFile::class);
		$newFile->expects($this->once())
			->method('putContent')
			->with($expected2->data());
		$newFile->expects($this->once())
			->method('getContent')
			->willReturn($expected2->data());
		$this->folder->expects($this->once())
			->method('newFile')
			->with('avatar.32.png')
			->willReturn($newFile);

		$this->assertEquals($expected2->data(), $this->avatar->get(32)->data());
	}

	public function testExistsNo() {
		$this->assertFalse($this->avatar->exists());
	}

	public function testExiststJPG() {
		$this->folder->method('fileExists')
			->willReturnMap([
				['avatar.jpg', true],
				['avatar.png', false],
			]);
		$this->assertTrue($this->avatar->exists());
	}

	public function testExistsPNG() {
		$this->folder->method('fileExists')
			->willReturnMap([
				['avatar.jpg', false],
				['avatar.png', true],
			]);
		$this->assertTrue($this->avatar->exists());
	}

	public function testSetAvatar() {
		$avatarFileJPG = $this->createMock(File::class);
		$avatarFileJPG->method('getName')
			->willReturn('avatar.jpg');
		$avatarFileJPG->expects($this->once())->method('delete');

		$avatarFilePNG = $this->createMock(File::class);
		$avatarFilePNG->method('getName')
			->willReturn('avatar.png');
		$avatarFilePNG->expects($this->once())->method('delete');

		$resizedAvatarFile = $this->createMock(File::class);
		$resizedAvatarFile->method('getName')
			->willReturn('avatar.32.jpg');
		$resizedAvatarFile->expects($this->once())->method('delete');

		$this->folder->method('getDirectoryListing')
			->willReturn([$avatarFileJPG, $avatarFilePNG, $resizedAvatarFile]);

		$generated = $this->createMock(ISimpleFile::class);
		$this->folder->method('getFile')
			->with('generated')
			->willReturn($generated);

		$newFile = $this->createMock(ISimpleFile::class);
		$this->folder->expects($this->once())
			->method('newFile')
			->with('avatar.png')
			->willReturn($newFile);

		$image = new \OC_Image();
		$image->loadFromFile(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$newFile->expects($this->once())
			->method('putContent')
			->with($image->data());

		$this->config->expects($this->exactly(3))
			->method('setUserValue');
		$this->config->expects($this->once())
			->method('getUserValue');

		$this->user->expects($this->exactly(1))->method('triggerChange');

		$this->avatar->set($image->data());
	}

	public function testGenerateSvgAvatar() {
		$avatar = $this->invokePrivate($this->avatar, 'getAvatarVector', [64, false]);

		$svg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
		<svg width="64" height="64" version="1.1" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg">
			<rect width="100%" height="100%" fill="#e5f2f9"></rect>
			<text x="50%" y="350" style="font-weight:normal;font-size:280px;font-family:\'Noto Sans\';text-anchor:middle;fill:#0082c9">A</text>
		</svg>';
		$this->assertEquals($avatar, $svg);
	}


	/**
	 * @dataProvider avatarTextData
	 */
	public function testGetAvatarText($displayName, $expectedAvatarText) {
		$user = $this->getUserWithDisplayName($displayName);
		$avatar = $this->getUserAvatar($user);

		$avatarText = $this->invokePrivate($avatar, 'getAvatarText');
		$this->assertEquals($expectedAvatarText, $avatarText);
	}

	public function testHashToInt() {
		$hashToInt = $this->invokePrivate($this->avatar, 'hashToInt', ['abcdef', 18]);
		$this->assertTrue(gettype($hashToInt) === 'integer');
	}

	public function testMixPalette() {
		$colorFrom = new \OCP\Color(0, 0, 0);
		$colorTo = new \OCP\Color(6, 12, 18);
		$steps = 6;
		$palette = \OCP\Color::mixPalette($steps, $colorFrom, $colorTo);
		foreach ($palette as $j => $color) {
			// calc increment
			$incR = $colorTo->red() / $steps * $j;
			$incG = $colorTo->green() / $steps * $j;
			$incB = $colorTo->blue() / $steps * $j;
			// ensure everything is equal
			$this->assertEquals($color, new \OCP\Color($incR, $incG, $incB));
		}
		$hashToInt = $this->invokePrivate($this->avatar, 'hashToInt', ['abcdef', 18]);
		$this->assertTrue(gettype($hashToInt) === 'integer');
	}

	private function getUserWithDisplayName($name) {
		$user = $this->createMock(User::class);
		$user->method('getDisplayName')->willReturn($name);
		return $user;
	}

	private function getUserAvatar($user) {
		/** @var \OCP\IL10N | \PHPUnit\Framework\MockObject\MockObject $l */
		$l = $this->createMock(IL10N::class);
		$l->method('t')->willReturnArgument(0);

		return new \OC\Avatar\UserAvatar(
			$this->folder,
			$l,
			$user,
			$this->createMock(LoggerInterface::class),
			$this->config
		);
	}
}
