<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC_Util;

/**
 * Class UtilTest
 *
 * @package Test
 * @group DB
 */
class UtilTest extends \Test\TestCase {
	public function testGetVersion() {
		$version = \OCP\Util::getVersion();
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

	public function testSanitizeHTML() {
		$badArray = [
			'While it is unusual to pass an array',
			'this function actually <blink>supports</blink> it.',
			'And therefore there needs to be a <script>alert("Unit"+\'test\')</script> for it!',
			[
				'And It Even May <strong>Nest</strong>',
			],
		];
		$goodArray = [
			'While it is unusual to pass an array',
			'this function actually &lt;blink&gt;supports&lt;/blink&gt; it.',
			'And therefore there needs to be a &lt;script&gt;alert(&quot;Unit&quot;+&#039;test&#039;)&lt;/script&gt; for it!',
			[
				'And It Even May &lt;strong&gt;Nest&lt;/strong&gt;'
			],
		];
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

	public function testEncodePath() {
		$component = '/§#@test%&^ä/-child';
		$result = OC_Util::encodePath($component);
		$this->assertEquals("/%C2%A7%23%40test%25%26%5E%C3%A4/-child", $result);
	}

	public function testIsNonUTF8Locale() {
		// OC_Util::isNonUTF8Locale() assumes escapeshellcmd('§') returns '' with non-UTF-8 locale.
		$locale = setlocale(LC_CTYPE, 0);
		setlocale(LC_CTYPE, 'C');
		$this->assertEquals('', escapeshellcmd('§'));
		$this->assertEquals('\'\'', escapeshellarg('§'));
		setlocale(LC_CTYPE, 'C.UTF-8');
		$this->assertEquals('§', escapeshellcmd('§'));
		$this->assertEquals('\'§\'', escapeshellarg('§'));
		setlocale(LC_CTYPE, $locale);
	}

	public function testFileInfoLoaded() {
		$expected = function_exists('finfo_open');
		$this->assertEquals($expected, \OC_Util::fileInfoLoaded());
	}

	public function testGetDefaultEmailAddress() {
		$email = \OCP\Util::getDefaultEmailAddress("no-reply");
		$this->assertEquals('no-reply@localhost', $email);
	}

	public function testGetDefaultEmailAddressFromConfig() {
		$config = \OC::$server->getConfig();
		$config->setSystemValue('mail_domain', 'example.com');
		$email = \OCP\Util::getDefaultEmailAddress("no-reply");
		$this->assertEquals('no-reply@example.com', $email);
		$config->deleteSystemValue('mail_domain');
	}

	public function testGetConfiguredEmailAddressFromConfig() {
		$config = \OC::$server->getConfig();
		$config->setSystemValue('mail_domain', 'example.com');
		$config->setSystemValue('mail_from_address', 'owncloud');
		$email = \OCP\Util::getDefaultEmailAddress("no-reply");
		$this->assertEquals('owncloud@example.com', $email);
		$config->deleteSystemValue('mail_domain');
		$config->deleteSystemValue('mail_from_address');
	}

	public function testGetInstanceIdGeneratesValidId() {
		\OC::$server->getConfig()->deleteSystemValue('instanceid');
		$instanceId = OC_Util::getInstanceId();
		$this->assertStringStartsWith('oc', $instanceId);
		$matchesRegex = preg_match('/^[a-z0-9]+$/', $instanceId);
		$this->assertSame(1, $matchesRegex);
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
		return [
			// valid names
			['boringname', true],
			['something.with.extension', true],
			['now with spaces', true],
			['.a', true],
			['..a', true],
			['.dotfile', true],
			['single\'quote', true],
			['  spaces before', true],
			['spaces after   ', true],
			['allowed chars including the crazy ones $%&_-^@!,()[]{}=;#', true],
			['汉字也能用', true],
			['und Ümläüte sind auch willkommen', true],
			// disallowed names
			['', false],
			['     ', false],
			['.', false],
			['..', false],
			['back\\slash', false],
			['sl/ash', false],
			['lt<lt', true],
			['gt>gt', true],
			['col:on', true],
			['double"quote', true],
			['pi|pe', true],
			['dont?ask?questions?', true],
			['super*star', true],
			['new\nline', false],

			// better disallow these to avoid unexpected trimming to have side effects
			[' ..', false],
			['.. ', false],
			['. ', false],
			[' .', false],

			// part files not allowed
			['.part', false],
			['notallowed.part', false],
			['neither.filepart', false],

			// part in the middle is ok
			['super movie part one.mkv', true],
			['super.movie.part.mkv', true],
		];
	}

	/**
	 * Test needUpgrade() when the core version is increased
	 */
	public function testNeedUpgradeCore() {
		$config = \OC::$server->getConfig();
		$oldConfigVersion = $config->getSystemValue('version', '0.0.0');
		$oldSessionVersion = \OC::$server->getSession()->get('OC_Version');

		$this->assertFalse(\OCP\Util::needUpgrade());

		$config->setSystemValue('version', '7.0.0.0');
		\OC::$server->getSession()->set('OC_Version', [7, 0, 0, 1]);
		self::invokePrivate(new \OCP\Util, 'needUpgradeCache', [null]);

		$this->assertTrue(\OCP\Util::needUpgrade());

		$config->setSystemValue('version', $oldConfigVersion);
		\OC::$server->getSession()->set('OC_Version', $oldSessionVersion);
		self::invokePrivate(new \OCP\Util, 'needUpgradeCache', [null]);

		$this->assertFalse(\OCP\Util::needUpgrade());
	}

	public function testCheckDataDirectoryValidity() {
		$dataDir = \OC::$server->getTempManager()->getTemporaryFolder();
		touch($dataDir . '/.ocdata');
		$errors = \OC_Util::checkDataDirectoryValidity($dataDir);
		$this->assertEmpty($errors);
		\OCP\Files::rmdirr($dataDir);

		$dataDir = \OC::$server->getTempManager()->getTemporaryFolder();
		// no touch
		$errors = \OC_Util::checkDataDirectoryValidity($dataDir);
		$this->assertNotEmpty($errors);
		\OCP\Files::rmdirr($dataDir);

		$errors = \OC_Util::checkDataDirectoryValidity('relative/path');
		$this->assertNotEmpty($errors);
	}

	protected function setUp(): void {
		parent::setUp();

		\OC_Util::$scripts = [];
		\OC_Util::$styles = [];
		self::invokePrivate(\OCP\Util::class, 'scripts', [[]]);
		self::invokePrivate(\OCP\Util::class, 'scriptDeps', [[]]);
	}
	protected function tearDown(): void {
		parent::tearDown();

		\OC_Util::$scripts = [];
		\OC_Util::$styles = [];
		self::invokePrivate(\OCP\Util::class, 'scripts', [[]]);
		self::invokePrivate(\OCP\Util::class, 'scriptDeps', [[]]);
	}

	public function testAddScript() {
		\OCP\Util::addScript('first', 'myFirstJSFile');
		\OCP\Util::addScript('core', 'myFancyJSFile1');
		\OCP\Util::addScript('files', 'myFancyJSFile2', 'core');
		\OCP\Util::addScript('myApp5', 'myApp5JSFile', 'myApp2');
		\OCP\Util::addScript('myApp', 'myFancyJSFile3');
		\OCP\Util::addScript('core', 'myFancyJSFile4');
		// after itself
		\OCP\Util::addScript('core', 'myFancyJSFile5', 'core');
		// add duplicate
		\OCP\Util::addScript('core', 'myFancyJSFile1');
		// dependency chain
		\OCP\Util::addScript('myApp4', 'myApp4JSFile', 'myApp3');
		\OCP\Util::addScript('myApp3', 'myApp3JSFile', 'myApp2');
		\OCP\Util::addScript('myApp2', 'myApp2JSFile', 'myApp');
		\OCP\Util::addScript('core', 'common');
		\OCP\Util::addScript('core', 'main');

		$scripts = \OCP\Util::getScripts();

		// Core should appear first
		$this->assertEquals(
			0,
			array_search('core/js/common', $scripts, true)
		);
		$this->assertEquals(
			1,
			array_search('core/js/main', $scripts, true)
		);
		$this->assertEquals(
			2,
			array_search('core/js/myFancyJSFile1', $scripts, true)
		);
		$this->assertEquals(
			3,
			array_search('core/js/myFancyJSFile4', $scripts, true)
		);

		// Dependencies should appear before their children
		$this->assertLessThan(
			array_search('files/js/myFancyJSFile2', $scripts, true),
			array_search('core/js/myFancyJSFile3', $scripts, true)
		);
		$this->assertLessThan(
			array_search('myApp2/js/myApp2JSFile', $scripts, true),
			array_search('myApp/js/myFancyJSFile3', $scripts, true)
		);
		$this->assertLessThan(
			array_search('myApp3/js/myApp3JSFile', $scripts, true),
			array_search('myApp2/js/myApp2JSFile', $scripts, true)
		);
		$this->assertLessThan(
			array_search('myApp4/js/myApp4JSFile', $scripts, true),
			array_search('myApp3/js/myApp3JSFile', $scripts, true)
		);
		$this->assertLessThan(
			array_search('myApp5/js/myApp5JSFile', $scripts, true),
			array_search('myApp2/js/myApp2JSFile', $scripts, true)
		);

		// No duplicates
		$this->assertEquals(
			$scripts,
			array_unique($scripts)
		);

		// All scripts still there
		$scripts = [
			"core/js/common",
			"core/js/main",
			"core/js/myFancyJSFile1",
			"core/js/myFancyJSFile4",
			"core/js/myFancyJSFile5",
			"first/l10n/en",
			"first/js/myFirstJSFile",
			"files/l10n/en",
			"files/js/myFancyJSFile2",
			"myApp/l10n/en",
			"myApp/js/myFancyJSFile3",
			"myApp2/l10n/en",
			"myApp2/js/myApp2JSFile",
			"myApp5/l10n/en",
			"myApp5/js/myApp5JSFile",
			"myApp3/l10n/en",
			"myApp3/js/myApp3JSFile",
			"myApp4/l10n/en",
			"myApp4/js/myApp4JSFile",
		];
		foreach ($scripts as $script) {
			$this->assertContains($script, $scripts);
		}
	}

	public function testAddScriptCircularDependency() {
		\OCP\Util::addScript('circular', 'file1', 'dependency');
		\OCP\Util::addScript('dependency', 'file2', 'circular');

		$scripts = \OCP\Util::getScripts();
		$this->assertContains('circular/js/file1', $scripts);
		$this->assertContains('dependency/js/file2', $scripts);
	}

	public function testAddVendorScript() {
		\OC_Util::addVendorScript('core', 'myFancyJSFile1');
		\OC_Util::addVendorScript('myApp', 'myFancyJSFile2');
		\OC_Util::addVendorScript('core', 'myFancyJSFile0', true);
		\OC_Util::addVendorScript('core', 'myFancyJSFile10', true);
		// add duplicate
		\OC_Util::addVendorScript('core', 'myFancyJSFile1');

		$this->assertEquals([
			'core/vendor/myFancyJSFile10',
			'core/vendor/myFancyJSFile0',
			'core/vendor/myFancyJSFile1',
			'myApp/vendor/myFancyJSFile2',
		], \OC_Util::$scripts);
		$this->assertEquals([], \OC_Util::$styles);
	}

	public function testAddTranslations() {
		\OC_Util::addTranslations('appId', 'de');

		$this->assertEquals([
			'appId/l10n/de'
		], \OC_Util::$scripts);
		$this->assertEquals([], \OC_Util::$styles);
	}

	public function testAddStyle() {
		\OC_Util::addStyle('core', 'myFancyCSSFile1');
		\OC_Util::addStyle('myApp', 'myFancyCSSFile2');
		\OC_Util::addStyle('core', 'myFancyCSSFile0', true);
		\OC_Util::addStyle('core', 'myFancyCSSFile10', true);
		// add duplicate
		\OC_Util::addStyle('core', 'myFancyCSSFile1');

		$this->assertEquals([], \OC_Util::$scripts);
		$this->assertEquals([
			'core/css/myFancyCSSFile10',
			'core/css/myFancyCSSFile0',
			'core/css/myFancyCSSFile1',
			'myApp/css/myFancyCSSFile2',
		], \OC_Util::$styles);
	}

	public function testAddVendorStyle() {
		\OC_Util::addVendorStyle('core', 'myFancyCSSFile1');
		\OC_Util::addVendorStyle('myApp', 'myFancyCSSFile2');
		\OC_Util::addVendorStyle('core', 'myFancyCSSFile0', true);
		\OC_Util::addVendorStyle('core', 'myFancyCSSFile10', true);
		// add duplicate
		\OC_Util::addVendorStyle('core', 'myFancyCSSFile1');

		$this->assertEquals([], \OC_Util::$scripts);
		$this->assertEquals([
			'core/vendor/myFancyCSSFile10',
			'core/vendor/myFancyCSSFile0',
			'core/vendor/myFancyCSSFile1',
			'myApp/vendor/myFancyCSSFile2',
		], \OC_Util::$styles);
	}

	public function testShortenMultibyteString() {
		$this->assertEquals('Short nuff', \OCP\Util::shortenMultibyteString('Short nuff', 255));
		$this->assertEquals('ABC', \OCP\Util::shortenMultibyteString('ABCDEF', 3));
		// each of the characters is 12 bytes
		$this->assertEquals('🙈', \OCP\Util::shortenMultibyteString('🙈🙊🙉', 16, 2));
	}
}
