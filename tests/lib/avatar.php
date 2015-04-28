<?php

/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OC\Avatar;

class Test_Avatar extends \Test\TestCase {
	private static $trashBinStatus;

	/** @var  @var string */
	private $user;

	protected function setUp() {
		parent::setUp();

		$this->user = $this->getUniqueID();
		$storage = new \OC\Files\Storage\Temporary(array());
		\OC\Files\Filesystem::mount($storage, array(), '/' . $this->user . '/');
	}

	public static function setUpBeforeClass() {
		self::$trashBinStatus = \OC_App::isEnabled('files_trashbin');
		\OC_App::disable('files_trashbin');
	}

	public static function tearDownAfterClass() {
		if (self::$trashBinStatus) {
			\OC_App::enable('files_trashbin');
		}
	}

	/**
	 * @return array
	 */
	public function traversalProvider() {
		return [
			['Pot\..\entiallyDangerousUsername'],
			['Pot/..\entiallyDangerousUsername'],
			['PotentiallyDangerousUsername/..'],
			['PotentiallyDangerousUsername\../'],
			['/../PotentiallyDangerousUsername'],
		];
	}

	/**
	 * @dataProvider traversalProvider
	 * @expectedException \Exception
	 * @expectedExceptionMessage Username may not contain slashes
	 * @param string $dangerousUsername
	 */
	public function testAvatarTraversal($dangerousUsername) {
		new Avatar($dangerousUsername);
	}

	public function testAvatar() {

		$avatar = new Avatar($this->user);

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
