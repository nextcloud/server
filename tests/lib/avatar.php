<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Avatar extends PHPUnit_Framework_TestCase {

	public function testAvatar() {
		$this->markTestSkipped("Setting custom avatars with encryption doesn't work yet");

		$avatar = new \OC_Avatar(\OC_User::getUser());

		$this->assertEquals(false, $avatar->get());

		$expected = new OC_Image(\OC::$SERVERROOT.'/tests/data/testavatar.png');
		$avatar->set($expected->data());
		$expected->resize(64);
		$this->assertEquals($expected->data(), $avatar->get()->data());

		$avatar->remove();
		$this->assertEquals(false, $avatar->get());
	}
}
