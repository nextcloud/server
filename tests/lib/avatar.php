<?php

/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
class Test_Avatar extends \Test\TestCase {

	private $user;

	protected function setUp() {
		parent::setUp();

		$this->user = $this->getUniqueID();
		$storage = new \OC\Files\Storage\Temporary(array());
		\OC\Files\Filesystem::mount($storage, array(), '/' . $this->user . '/');
	}

	public function testAvatar() {

		$avatar = new \OC_Avatar($this->user);

		$this->assertEquals(false, $avatar->get());

		$expected = new OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$expected->resize(64);
		$avatar->set($expected->data());
		$this->assertEquals($expected->data(), $avatar->get()->data());

		$avatar->remove();
		$this->assertEquals(false, $avatar->get());
	}

	public function testAvatarApi() {
		$avatarManager = \OC::$server->getAvatarManager();
		$avatar = $avatarManager->getAvatar($this->user);

		$this->assertEquals(false, $avatar->get());

		$expected = new OC_Image(\OC::$SERVERROOT . '/tests/data/testavatar.png');
		$expected->resize(64);
		$avatar->set($expected->data());
		$this->assertEquals($expected->data(), $avatar->get()->data());

		$avatar->remove();
		$this->assertEquals(false, $avatar->get());
	}
}
