<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Avatar extends PHPUnit_Framework_TestCase {

	public function testAvatar() {
		$expected = \OC_Avatar::getDefaultAvatar()->data();
		$this->assertEquals($expected, \OC_Avatar::get(\OC_User::getUser())->data());

		$expected = new OC_Image(\OC::$SERVERROOT.'/tests/data/testavatar.png');
		\OC_Avatar::set(\OC_User::getUser(), $expected->data());
		$expected->resize(64);
		$this->assertEquals($expected->data(), \OC_Avatar::get(\OC_User::getUser())->data());

		\OC_Avatar::set(\OC_User::getUser(), false);
		$expected = \OC_Avatar::getDefaultAvatar()->data();
		$this->assertEquals($expected, \OC_Avatar::get(\OC_User::getUser())->data());
	}

	/*public function testDefaultAvatar() {
		$img = new \OC_Image(OC::$SERVERROOT.'/core/img/defaultavatar.png');
		$img->resize(128);
		$this->assertEquals($img->data(), \OC_Avatar::getDefaultAvatar(\OC_User::getUser(), 128)->data());
	}*/
}
