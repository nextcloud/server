<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\Files\SimpleFS\SimpleFolder;
use OC\User\User;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;

class AvatarTest extends \Test\TestCase {
	/** @var Folder | \PHPUnit_Framework_MockObject_MockObject */
	private $folder;

	/** @var \OC\Avatar */
	private $avatar;

	/** @var \OC\User\User | \PHPUnit_Framework_MockObject_MockObject $user */
	private $user;

	/** @var \OCP\IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	public function setUp() {
		parent::setUp();

		$this->folder = $this->createMock(SimpleFolder::class);
		/** @var \OCP\IL10N | \PHPUnit_Framework_MockObject_MockObject $l */
		$l = $this->createMock(IL10N::class);
		$l->method('t')->will($this->returnArgument(0));
		$this->user = $this->createMock(User::class);
		$this->config = $this->createMock(IConfig::class);

		$this->avatar = new \OC\Avatar(
			$this->folder,
			$l,
			$this->user,
			$this->createMock(ILogger::class),
			$this->config
		);
	}

	public function testGetNoAvatar() {
		$this->assertEquals(false, $this->avatar->get());
	}

	public function testGetAvatarSizeMatch() {
		$this->folder->method('fileExists')
			->will($this->returnValueMap([
				['avatar.jpg', true],
				['avatar.128.jpg', true],
			]));

		$expected = new \OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');

		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn($expected->data());
		$this->folder->method('getFile')->with('avatar.128.jpg')->willReturn($file);

		$this->assertEquals($expected->data(), $this->avatar->get(128)->data());
	}

	public function testGetAvatarSizeMinusOne() {
		$this->folder->method('fileExists')
			->will($this->returnValueMap([
				['avatar.jpg', true],
			]));

		$expected = new \OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');

		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn($expected->data());
		$this->folder->method('getFile')->with('avatar.jpg')->willReturn($file);

		$this->assertEquals($expected->data(), $this->avatar->get(-1)->data());
	}

	public function testGetAvatarNoSizeMatch() {
		$this->folder->method('fileExists')
			->will($this->returnValueMap([
				['avatar.png', true],
				['avatar.32.png', false],
			]));

		$expected = new \OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$expected2 = new \OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$expected2->resize(32);

		$file = $this->createMock(File::class);
		$file->method('getContent')->willReturn($expected->data());

		$this->folder->method('getFile')
			->will($this->returnCallback(
				function($path) use ($file) {
					if ($path === 'avatar.png') {
						return $file;
					} else {
						throw new \OCP\Files\NotFoundException;
					}
				}
			));

		$newFile = $this->createMock(File::class);
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
			->will($this->returnValueMap([
				['avatar.jpg', true],
				['avatar.png', false],
			]));
		$this->assertTrue($this->avatar->exists());
	}

	public function testExistsPNG() {
		$this->folder->method('fileExists')
			->will($this->returnValueMap([
				['avatar.jpg', false],
				['avatar.png', true],
			]));
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

		$nonAvatarFile = $this->createMock(File::class);
		$nonAvatarFile->method('getName')
			->willReturn('avatarX');
		$nonAvatarFile->expects($this->never())->method('delete');

		$this->folder->method('getDirectoryListing')
			->willReturn([$avatarFileJPG, $avatarFilePNG, $resizedAvatarFile, $nonAvatarFile]);

		$newFile = $this->createMock(File::class);
		$this->folder->expects($this->once())
			->method('newFile')
			->with('avatar.png')
			->willReturn($newFile);

		$image = new \OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$newFile->expects($this->once())
			->method('putContent')
			->with($image->data());

		$this->config->expects($this->once())
			->method('setUserValue');
		$this->config->expects($this->once())
			->method('getUserValue');

		// One on remove and once on setting the new avatar
		$this->user->expects($this->exactly(2))->method('triggerChange');

		$this->avatar->set($image->data());
	}

}
