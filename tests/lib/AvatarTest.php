<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OCP\Files\Folder;

class AvatarTest extends \Test\TestCase {
	/** @var Folder | \PHPUnit_Framework_MockObject_MockObject */
	private $folder;

	/** @var \OC\Avatar */
	private $avatar;

	/** @var \OC\User\User | \PHPUnit_Framework_MockObject_MockObject $user */
	private $user;

	public function setUp() {
		parent::setUp();

		$this->folder = $this->getMock('\OCP\Files\Folder');
		/** @var \OCP\IL10N | \PHPUnit_Framework_MockObject_MockObject $l */
		$l = $this->getMock('\OCP\IL10N');
		$l->method('t')->will($this->returnArgument(0));
		$this->user = $this->getMockBuilder('\OC\User\User')->disableOriginalConstructor()->getMock();
		$this->avatar = new \OC\Avatar($this->folder, $l, $this->user, $this->getMock('\OCP\ILogger'));
	}

	public function testGetNoAvatar() {
		$this->assertEquals(false, $this->avatar->get());
	}

	public function testGetAvatarSizeMatch() {
		$this->folder->method('nodeExists')
			->will($this->returnValueMap([
				['avatar.jpg', true],
				['avatar.128.jpg', true],
			]));

		$expected = new \OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');

		$file = $this->getMock('\OCP\Files\File');
		$file->method('getContent')->willReturn($expected->data());
		$this->folder->method('get')->with('avatar.128.jpg')->willReturn($file);

		$this->assertEquals($expected->data(), $this->avatar->get(128)->data());
	}

	public function testGetAvatarSizeMinusOne() {
		$this->folder->method('nodeExists')
			->will($this->returnValueMap([
				['avatar.jpg', true],
			]));

		$expected = new \OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');

		$file = $this->getMock('\OCP\Files\File');
		$file->method('getContent')->willReturn($expected->data());
		$this->folder->method('get')->with('avatar.jpg')->willReturn($file);

		$this->assertEquals($expected->data(), $this->avatar->get(-1)->data());
	}

	public function testGetAvatarNoSizeMatch() {
		$this->folder->method('nodeExists')
			->will($this->returnValueMap([
				['avatar.png', true],
				['avatar.32.png', false],
			]));

		$expected = new \OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$expected2 = new \OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$expected2->resize(32);

		$file = $this->getMock('\OCP\Files\File');
		$file->method('getContent')->willReturn($expected->data());

		$this->folder->method('get')
			->will($this->returnCallback(
				function($path) use ($file) {
					if ($path === 'avatar.png') {
						return $file;
					} else {
						throw new \OCP\Files\NotFoundException;
					}
				}
			));

		$newFile = $this->getMock('\OCP\Files\File');
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
		$this->folder->method('nodeExists')
			->will($this->returnValueMap([
				['avatar.jpg', true],
				['avatar.png', false],
			]));
		$this->assertTrue($this->avatar->exists());
	}

	public function testExistsPNG() {
		$this->folder->method('nodeExists')
			->will($this->returnValueMap([
				['avatar.jpg', false],
				['avatar.png', true],
			]));
		$this->assertTrue($this->avatar->exists());
	}

	public function testSetAvatar() {
		$avatarFileJPG = $this->getMock('\OCP\Files\File');
		$avatarFileJPG->method('getName')
			->willReturn('avatar.jpg');
		$avatarFileJPG->expects($this->once())->method('delete');

		$avatarFilePNG = $this->getMock('\OCP\Files\File');
		$avatarFilePNG->method('getName')
			->willReturn('avatar.png');
		$avatarFilePNG->expects($this->once())->method('delete');

		$resizedAvatarFile = $this->getMock('\OCP\Files\File');
		$resizedAvatarFile->method('getName')
			->willReturn('avatar.32.jpg');
		$resizedAvatarFile->expects($this->once())->method('delete');

		$nonAvatarFile = $this->getMock('\OCP\Files\File');
		$nonAvatarFile->method('getName')
			->willReturn('avatarX');
		$nonAvatarFile->expects($this->never())->method('delete');

		$this->folder->method('getDirectoryListing')
			->willReturn([$avatarFileJPG, $avatarFilePNG, $resizedAvatarFile, $nonAvatarFile]);

		$newFile = $this->getMock('\OCP\Files\File');
		$this->folder->expects($this->once())
			->method('newFile')
			->with('avatar.png')
			->willReturn($newFile);

		$image = new \OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$newFile->expects($this->once())
			->method('putContent')
			->with($image->data());

		// One on remove and once on setting the new avatar
		$this->user->expects($this->exactly(2))->method('triggerChange');

		$this->avatar->set($image->data());
	}

}
