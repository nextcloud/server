<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Util extends \Test\TestCase {
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
		$expected = 'October 13, 2012 at 11:53:25 AM GMT+0';
		$this->assertEquals($expected, $result);

		$result = OC_Util::formatDate(1102831200, true);
		$expected = 'December 12, 2004';
		$this->assertEquals($expected, $result);
	}

	function testFormatDateWithTZ() {
		date_default_timezone_set("UTC");

		$result = OC_Util::formatDate(1350129205, false, 'Europe/Berlin');
		$expected = 'October 13, 2012 at 1:53:25 PM GMT+0';
		$this->assertEquals($expected, $result);
	}

	/**
	 * @expectedException Exception
	 */
	function testFormatDateWithInvalidTZ() {
		OC_Util::formatDate(1350129205, false, 'Mordor/Barad-dûr');
	}

	function testFormatDateWithTZFromSession() {
		date_default_timezone_set("UTC");

		\OC::$server->getSession()->set('timezone', 3);
		$result = OC_Util::formatDate(1350129205, false);
		$expected = 'October 13, 2012 at 2:53:25 PM GMT+0';
		$this->assertEquals($expected, $result);
	}

	function testCallRegister() {
		$result = strlen(OC_Util::callRegister());
		$this->assertEquals(30, $result);
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
		$instanceId = OC_Util::getInstanceId();
		$this->assertStringStartsWith('oc', $instanceId);
		$matchesRegex = preg_match('/^[a-z0-9]+$/', $instanceId);
		$this->assertSame(1, $matchesRegex);
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

	/**
	 * Test default apps
	 *
	 * @dataProvider defaultAppsProvider
	 */
	function testDefaultApps($defaultAppConfig, $expectedPath, $enabledApps) {
		$oldDefaultApps = \OCP\Config::getSystemValue('core', 'defaultapp', '');
		// CLI is doing messy stuff with the webroot, so need to work it around
		$oldWebRoot = \OC::$WEBROOT;
		\OC::$WEBROOT = '';

		Dummy_OC_App::setEnabledApps($enabledApps);
		// need to set a user id to make sure enabled apps are read from cache
		\OC_User::setUserId(uniqid());
		\OCP\Config::setSystemValue('defaultapp', $defaultAppConfig);
		$this->assertEquals('http://localhost/' . $expectedPath, \OC_Util::getDefaultPageUrl());

		// restore old state
		\OC::$WEBROOT = $oldWebRoot;
		Dummy_OC_App::restore();
		\OCP\Config::setSystemValue('defaultapp', $oldDefaultApps);
		\OC_User::setUserId(null);
	}

	function defaultAppsProvider() {
		return array(
			// none specified, default to files
			array(
				'',
				'index.php/apps/files/',
				array('files'),
			),
			// unexisting or inaccessible app specified, default to files
			array(
				'unexist',
				'index.php/apps/files/',
				array('files'),
			),
			// non-standard app
			array(
				'calendar',
				'index.php/apps/calendar/',
				array('files', 'calendar'),
			),
			// non-standard app with fallback
			array(
				'contacts,calendar',
				'index.php/apps/calendar/',
				array('files', 'calendar'),
			),
		);
	}

	/**
	 * Test needUpgrade() when the core version is increased
	 */
	public function testNeedUpgradeCore() {
		$oldConfigVersion = OC_Config::getValue('version', '0.0.0');
		$oldSessionVersion = \OC::$server->getSession()->get('OC_Version');

		$this->assertFalse(\OCP\Util::needUpgrade());

		OC_Config::setValue('version', '7.0.0.0');
		\OC::$server->getSession()->set('OC_Version', array(7, 0, 0, 1));

		$this->assertTrue(\OCP\Util::needUpgrade());

		OC_Config::setValue('version', $oldConfigVersion);
		$oldSessionVersion = \OC::$server->getSession()->set('OC_Version', $oldSessionVersion);

		$this->assertFalse(\OCP\Util::needUpgrade());
	}
}

/**
 * Dummy OC Apps class to make it possible to override
 * enabled apps
 */
class Dummy_OC_App extends OC_App {
	private static $enabledAppsCacheBackup;

	public static function setEnabledApps($enabledApps) {
		self::$enabledAppsCacheBackup = self::$enabledAppsCache;
		self::$enabledAppsCache = $enabledApps;
	}

	public static function restore() {
		self::$enabledAppsCache = self::$enabledAppsCacheBackup;
	}
}
