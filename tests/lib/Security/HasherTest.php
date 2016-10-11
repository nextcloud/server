<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Security;

use OC\Security\Hasher;

/**
 * Class HasherTest
 */
class HasherTest extends \Test\TestCase {

	/**
	 * @return array
	 */
	public function versionHashProvider()
	{
		return array(
			array('asf32äà$$a.|3', null),
			array('asf32äà$$a.|3|5', null),
			array('1|2|3|4', array('version' => 1, 'hash' => '2|3|4')),
			array('1|我看|这本书。 我看這本書', array('version' => 1, 'hash' => '我看|这本书。 我看這本書'))
		);
	}

	/**
	 * @return array
	 */
	public function allHashProviders()
	{
		return array(
			// Bogus values
			array(null, 'asf32äà$$a.|3', false),
			array(null, false, false),

			// Valid SHA1 strings
			array('password', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', true),
			array('owncloud.com', '27a4643e43046c3569e33b68c1a4b15d31306d29', true),

			// Invalid SHA1 strings
			array('InvalidString', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', false),
			array('AnotherInvalidOne', '27a4643e43046c3569e33b68c1a4b15d31306d29', false),

			// Valid legacy password string with password salt "6Wow67q1wZQZpUUeI6G2LsWUu4XKx"
			array('password', '$2a$08$emCpDEl.V.QwPWt5gPrqrOhdpH6ailBmkj2Hd2vD5U8qIy20HBe7.', true),
			array('password', '$2a$08$yjaLO4ev70SaOsWZ9gRS3eRSEpHVsmSWTdTms1949mylxJ279hzo2', true),
			array('password', '$2a$08$.jNRG/oB4r7gHJhAyb.mDupNUAqTnBIW/tWBqFobaYflKXiFeG0A6', true),
			array('owncloud.com', '$2a$08$YbEsyASX/hXVNMv8hXQo7ezreN17T8Jl6PjecGZvpX.Ayz2aUyaZ2', true),
			array('owncloud.com', '$2a$11$cHdDA2IkUP28oNGBwlL7jO/U3dpr8/0LIjTZmE8dMPA7OCUQsSTqS', true),
			array('owncloud.com', '$2a$08$GH.UoIfJ1e.qeZ85KPqzQe6NR8XWRgJXWIUeE1o/j1xndvyTA1x96', true),

			// Invalid legacy passwords
			array('password', '$2a$08$oKAQY5IhnZocP.61MwP7xu7TNeOb7Ostvk3j6UpacvaNMs.xRj7O2', false),

			// Valid passwords "6Wow67q1wZQZpUUeI6G2LsWUu4XKx"
			array('password', '1|$2a$05$ezAE0dkwk57jlfo6z5Pql.gcIK3ReXT15W7ITNxVS0ksfhO/4E4Kq', true),
			array('password', '1|$2a$05$4OQmloFW4yTVez2MEWGIleDO9Z5G9tWBXxn1vddogmKBQq/Mq93pe', true),
			array('password', '1|$2a$11$yj0hlp6qR32G9exGEXktB.yW2rgt2maRBbPgi3EyxcDwKrD14x/WO', true),
			array('owncloud.com', '1|$2a$10$Yiss2WVOqGakxuuqySv5UeOKpF8d8KmNjuAPcBMiRJGizJXjA2bKm', true),
			array('owncloud.com', '1|$2a$10$v9mh8/.mF/Ut9jZ7pRnpkuac3bdFCnc4W/gSumheQUi02Sr.xMjPi', true),
			array('owncloud.com', '1|$2a$05$ST5E.rplNRfDCzRpzq69leRzsTGtY7k88h9Vy2eWj0Ug/iA9w5kGK', true),

			// Invalid passwords
			array('password', '0|$2a$08$oKAQY5IhnZocP.61MwP7xu7TNeOb7Ostvk3j6UpacvaNMs.xRj7O2', false),
			array('password', '1|$2a$08$oKAQY5IhnZocP.61MwP7xu7TNeOb7Ostvk3j6UpacvaNMs.xRj7O2', false),
			array('password', '2|$2a$08$oKAQY5IhnZocP.61MwP7xu7TNeOb7Ostvk3j6UpacvaNMs.xRj7O2', false),
		);
	}

	/** @var Hasher */
	protected $hasher;

	/** @var \OCP\IConfig */
	protected $config;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();

		$this->hasher = new Hasher($this->config);
	}

	function testHash() {
		$hash = $this->hasher->hash('String To Hash');
		$this->assertNotNull($hash);
	}

	/**
	 * @dataProvider versionHashProvider
	 */
	function testSplitHash($hash, $expected) {
		$relativePath = self::invokePrivate($this->hasher, 'splitHash', array($hash));
		$this->assertSame($expected, $relativePath);
	}


	/**
	 * @dataProvider allHashProviders
	 */
	function testVerify($password, $hash, $expected) {
		$this->config
			->expects($this->any())
			->method('getSystemValue')
			->with('passwordsalt', null)
			->will($this->returnValue('6Wow67q1wZQZpUUeI6G2LsWUu4XKx'));

		$result = $this->hasher->verify($password, $hash);
		$this->assertSame($expected, $result);
	}

}
