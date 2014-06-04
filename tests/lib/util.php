<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Util extends PHPUnit_Framework_TestCase {
	public function testGetVersion() {
		$version = \OC_Util::getVersion();
		$this->assertTrue(is_array($version));
		foreach ($version as $num) {
			$this->assertTrue(is_int($num));
		}
	}

	public function testGetVersionString() {
		$version = \OC_Util::getVersionString();
		$this->assertTrue(is_string($version));
	}

	public function testGetEditionString() {
		$edition = \OC_Util::getEditionString();
		$this->assertTrue(is_string($edition));
	}

	function testFormatDate() {
		date_default_timezone_set("UTC");

		$result = OC_Util::formatDate(1350129205);
		$expected = 'October 13, 2012 11:53';
		$this->assertEquals($expected, $result);

		$result = OC_Util::formatDate(1102831200, true);
		$expected = 'December 12, 2004';
		$this->assertEquals($expected, $result);
	}

	function testCallRegister() {
		$result = strlen(OC_Util::callRegister());
		$this->assertEquals(20, $result);
	}

	function testSanitizeHTML() {
		$badArray = array(
			'While it is unusual to pass an array',
			'this function actually <blink>supports</blink> it.',
			'And therefore there needs to be a <script>alert("Unit"+\'test\')</script> for it!'
		);
		$goodArray = array(
			'While it is unusual to pass an array',
			'this function actually &lt;blink&gt;supports&lt;/blink&gt; it.',
			'And therefore there needs to be a &lt;script&gt;alert(&quot;Unit&quot;+&#039;test&#039;)&lt;/script&gt; for it!'
		);
		$result = OC_Util::sanitizeHTML($badArray);
		$this->assertEquals($goodArray, $result);

		$badString = '<img onload="alert(1)" />';
		$result = OC_Util::sanitizeHTML($badString);
		$this->assertEquals('&lt;img onload=&quot;alert(1)&quot; /&gt;', $result);

		$badString = "<script>alert('Hacked!');</script>";
		$result = OC_Util::sanitizeHTML($badString);
		$this->assertEquals('&lt;script&gt;alert(&#039;Hacked!&#039;);&lt;/script&gt;', $result);

		$goodString = 'This is a good string without HTML.';
		$result = OC_Util::sanitizeHTML($goodString);
		$this->assertEquals('This is a good string without HTML.', $result);
	}

	function testEncodePath(){
		$component = '/§#@test%&^ä/-child';
		$result = OC_Util::encodePath($component);
		$this->assertEquals("/%C2%A7%23%40test%25%26%5E%C3%A4/-child", $result);
	}

	public function testFileInfoLoaded() {
		$expected = function_exists('finfo_open');
		$this->assertEquals($expected, \OC_Util::fileInfoLoaded());
	}

	public function testIsInternetConnectionEnabled() {
		\OC_Config::setValue("has_internet_connection", false);
		$this->assertFalse(\OC_Util::isInternetConnectionEnabled());

		\OC_Config::setValue("has_internet_connection", true);
		$this->assertTrue(\OC_Util::isInternetConnectionEnabled());
	}

	function testGenerateRandomBytes() {
		$result = strlen(OC_Util::generateRandomBytes(59));
		$this->assertEquals(59, $result);
	}

	function testGetDefaultEmailAddress() {
		$email = \OCP\Util::getDefaultEmailAddress("no-reply");
		$this->assertEquals('no-reply@localhost', $email);
	}

	function testGetDefaultEmailAddressFromConfig() {
		OC_Config::setValue('mail_domain', 'example.com');
		$email = \OCP\Util::getDefaultEmailAddress("no-reply");
		$this->assertEquals('no-reply@example.com', $email);
		OC_Config::deleteKey('mail_domain');
	}

	function testGetConfiguredEmailAddressFromConfig() {
		OC_Config::setValue('mail_domain', 'example.com');
		OC_Config::setValue('mail_from_address', 'owncloud');
		$email = \OCP\Util::getDefaultEmailAddress("no-reply");
		$this->assertEquals('owncloud@example.com', $email);
		OC_Config::deleteKey('mail_domain');
		OC_Config::deleteKey('mail_from_address');
	}

	function testGetInstanceIdGeneratesValidId() {
		OC_Config::deleteKey('instanceid');
		$this->assertStringStartsWith('oc', OC_Util::getInstanceId());
	}

	/**
	 * Tests that the home storage is not wrapped when no quota exists.
	 */
	function testHomeStorageWrapperWithoutQuota() {
		$user1 = uniqid();
		\OC_User::createUser($user1, 'test');
		OC_Preferences::setValue($user1, 'files', 'quota', 'none');
		\OC_User::setUserId($user1);

		\OC_Util::setupFS($user1);

		$userMount = \OC\Files\Filesystem::getMountManager()->find('/' . $user1 . '/');
		$this->assertNotNull($userMount);
		$this->assertNotInstanceOf('\OC\Files\Storage\Wrapper\Quota', $userMount->getStorage());

		// clean up
		\OC_User::setUserId('');
		\OC_User::deleteUser($user1);
		OC_Preferences::deleteUser($user1);
		\OC_Util::tearDownFS();
	}

	/**
	 * Tests that the home storage is not wrapped when no quota exists.
	 */
	function testHomeStorageWrapperWithQuota() {
		$user1 = uniqid();
		\OC_User::createUser($user1, 'test');
		OC_Preferences::setValue($user1, 'files', 'quota', '1024');
		\OC_User::setUserId($user1);

		\OC_Util::setupFS($user1);

		$userMount = \OC\Files\Filesystem::getMountManager()->find('/' . $user1 . '/');
		$this->assertNotNull($userMount);
		$this->assertTrue($userMount->getStorage()->instanceOfStorage('\OC\Files\Storage\Wrapper\Quota'));

		// ensure that root wasn't wrapped
		$rootMount = \OC\Files\Filesystem::getMountManager()->find('/');
		$this->assertNotNull($rootMount);
		$this->assertNotInstanceOf('\OC\Files\Storage\Wrapper\Quota', $rootMount->getStorage());

		// clean up
		\OC_User::setUserId('');
		\OC_User::deleteUser($user1);
		OC_Preferences::deleteUser($user1);
		\OC_Util::tearDownFS();
	}

	/**
	 * @dataProvider baseNameProvider
	 */
	public function testBaseName($expected, $file)
	{
		$base = \OC_Util::basename($file);
		$this->assertEquals($expected, $base);
	}

	public function baseNameProvider()
	{
		return array(
			array('public_html', '/home/user/public_html/'),
			array('public_html', '/home/user/public_html'),
			array('', '/'),
			array('public_html', 'public_html'),
			array('442aa682de2a64db1e010f50e60fd9c9', 'local::C:\Users\ADMINI~1\AppData\Local\Temp\2/442aa682de2a64db1e010f50e60fd9c9/')
		);
	}

	/**
	 * @dataProvider filenameValidationProvider
	 */
	public function testFilenameValidation($file, $valid) {
		// private API
		$this->assertEquals($valid, \OC_Util::isValidFileName($file));
		// public API
		$this->assertEquals($valid, \OCP\Util::isValidFileName($file));
	}

	public function filenameValidationProvider() {
		return array(
			// valid names
			array('boringname', true),
			array('something.with.extension', true),
			array('now with spaces', true),
			array('.a', true),
			array('..a', true),
			array('.dotfile', true),
			array('single\'quote', true),
			array('  spaces before', true),
			array('spaces after   ', true),
			array('allowed chars including the crazy ones $%&_-^@!,()[]{}=;#', true),
			array('汉字也能用', true),
			array('und Ümläüte sind auch willkommen', true),
			// disallowed names
			array('', false),
			array('     ', false),
			array('.', false),
			array('..', false),
			array('back\\slash', false),
			array('sl/ash', false),
			array('lt<lt', false),
			array('gt>gt', false),
			array('col:on', false),
			array('double"quote', false),
			array('pi|pe', false),
			array('dont?ask?questions?', false),
			array('super*star', false),
			array('new\nline', false),
			// better disallow these to avoid unexpected trimming to have side effects
			array(' ..', false),
			array('.. ', false),
			array('. ', false),
			array(' .', false),
		);
	}

	/**
	 * @dataProvider dataProviderForTestIsSharingDisabledForUser
	 * @param array $groups existing groups
	 * @param array $membership groups the user belong to
	 * @param array $excludedGroups groups which should be excluded from sharing
	 * @param bool $expected expected result
	 */
	function testIsSharingDisabledForUser($groups, $membership, $excludedGroups, $expected) {
		$uid = "user1";
		\OC_User::setUserId($uid);

		\OC_User::createUser($uid, "passwd");

		foreach($groups as $group) {
			\OC_Group::createGroup($group);
		}

		foreach($membership as $group) {
			\OC_Group::addToGroup($uid, $group);
		}

		$appConfig = \OC::$server->getAppConfig();
		$appConfig->setValue('core', 'shareapi_exclude_groups_list', implode(',', $excludedGroups));
		$appConfig->setValue('core', 'shareapi_exclude_groups', 'yes');

		$result = \OCP\Util::isSharingDisabledForUser();

		$this->assertSame($expected, $result);

		// cleanup
		\OC_User::deleteUser($uid);
		\OC_User::setUserId('');

		foreach($groups as $group) {
			\OC_Group::deleteGroup($group);
		}

		$appConfig->setValue('core', 'shareapi_exclude_groups_list', '');
		$appConfig->setValue('core', 'shareapi_exclude_groups', 'no');

	}

	public function dataProviderForTestIsSharingDisabledForUser()    {
		return array(
			// existing groups, groups the user belong to, groups excluded from sharing, expected result
			array(array('g1', 'g2', 'g3'), array(), array('g1'), false),
			array(array('g1', 'g2', 'g3'), array(), array(), false),
			array(array('g1', 'g2', 'g3'), array('g2'), array('g1'), false),
			array(array('g1', 'g2', 'g3'), array('g2'), array(), false),
			array(array('g1', 'g2', 'g3'), array('g1', 'g2'), array('g1'), false),
			array(array('g1', 'g2', 'g3'), array('g1', 'g2'), array('g1', 'g2'), true),
			array(array('g1', 'g2', 'g3'), array('g1', 'g2'), array('g1', 'g2', 'g3'), true),
        );
    }
}
