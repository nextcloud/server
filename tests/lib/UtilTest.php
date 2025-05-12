<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC_Util;
use OCP\Util;

/**
 * Class UtilTest
 *
 * @package Test
 * @group DB
 */
class UtilTest extends \Test\TestCase {
	public function testGetVersion(): void {
		$version = Util::getVersion();
		$this->assertTrue(is_array($version));
		foreach ($version as $num) {
			$this->assertTrue(is_int($num));
		}
	}

	public function testSanitizeHTML(): void {
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
		$result = Util::sanitizeHTML($badArray);
		$this->assertEquals($goodArray, $result);

		$badString = '<img onload="alert(1)" />';
		$result = Util::sanitizeHTML($badString);
		$this->assertEquals('&lt;img onload=&quot;alert(1)&quot; /&gt;', $result);

		$badString = "<script>alert('Hacked!');</script>";
		$result = Util::sanitizeHTML($badString);
		$this->assertEquals('&lt;script&gt;alert(&#039;Hacked!&#039;);&lt;/script&gt;', $result);

		$goodString = 'This is a good string without HTML.';
		$result = Util::sanitizeHTML($goodString);
		$this->assertEquals('This is a good string without HTML.', $result);
	}

	public function testEncodePath(): void {
		$component = '/§#@test%&^ä/-child';
		$result = Util::encodePath($component);
		$this->assertEquals('/%C2%A7%23%40test%25%26%5E%C3%A4/-child', $result);
	}

	public function testIsNonUTF8Locale(): void {
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

	public function testFileInfoLoaded(): void {
		$expected = function_exists('finfo_open');
		$this->assertEquals($expected, \OC_Util::fileInfoLoaded());
	}

	/**
	 * Host is "localhost" this is a valid for emails,
	 * but not for default strict email verification that requires a top level domain.
	 * So we check that with strict email verification we fallback to the default
	 */
	public function testGetDefaultEmailAddressStrict(): void {
		$email = Util::getDefaultEmailAddress('no-reply');
		$this->assertEquals('no-reply@localhost.localdomain', $email);
	}

	/**
	 * If no strict email check is enabled "localhost" should validate as a valid email domain
	 */
	public function testGetDefaultEmailAddress(): void {
		$config = \OC::$server->getConfig();
		$config->setAppValue('core', 'enforce_strict_email_check', 'no');
		$email = Util::getDefaultEmailAddress('no-reply');
		$this->assertEquals('no-reply@localhost', $email);
		$config->deleteAppValue('core', 'enforce_strict_email_check');
	}

	public function testGetDefaultEmailAddressFromConfig(): void {
		$config = \OC::$server->getConfig();
		$config->setSystemValue('mail_domain', 'example.com');
		$email = Util::getDefaultEmailAddress('no-reply');
		$this->assertEquals('no-reply@example.com', $email);
		$config->deleteSystemValue('mail_domain');
	}

	public function testGetConfiguredEmailAddressFromConfig(): void {
		$config = \OC::$server->getConfig();
		$config->setSystemValue('mail_domain', 'example.com');
		$config->setSystemValue('mail_from_address', 'owncloud');
		$email = Util::getDefaultEmailAddress('no-reply');
		$this->assertEquals('owncloud@example.com', $email);
		$config->deleteSystemValue('mail_domain');
		$config->deleteSystemValue('mail_from_address');
	}

	public function testGetInstanceIdGeneratesValidId(): void {
		\OC::$server->getConfig()->deleteSystemValue('instanceid');
		$instanceId = OC_Util::getInstanceId();
		$this->assertStringStartsWith('oc', $instanceId);
		$matchesRegex = preg_match('/^[a-z0-9]+$/', $instanceId);
		$this->assertSame(1, $matchesRegex);
	}

	/**
	 * Test needUpgrade() when the core version is increased
	 */
	public function testNeedUpgradeCore(): void {
		$config = \OC::$server->getConfig();
		$oldConfigVersion = $config->getSystemValue('version', '0.0.0');
		$oldSessionVersion = \OC::$server->getSession()->get('OC_Version');

		$this->assertFalse(Util::needUpgrade());

		$config->setSystemValue('version', '7.0.0.0');
		\OC::$server->getSession()->set('OC_Version', [7, 0, 0, 1]);
		self::invokePrivate(new Util, 'needUpgradeCache', [null]);

		$this->assertTrue(Util::needUpgrade());

		$config->setSystemValue('version', $oldConfigVersion);
		\OC::$server->getSession()->set('OC_Version', $oldSessionVersion);
		self::invokePrivate(new Util, 'needUpgradeCache', [null]);

		$this->assertFalse(Util::needUpgrade());
	}

	public function testCheckDataDirectoryValidity(): void {
		$dataDir = \OC::$server->getTempManager()->getTemporaryFolder();
		touch($dataDir . '/.ncdata');
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

		\OC_Util::$styles = [];
		self::invokePrivate(Util::class, 'scripts', [[]]);
		self::invokePrivate(Util::class, 'scriptDeps', [[]]);
	}
	protected function tearDown(): void {
		parent::tearDown();

		\OC_Util::$styles = [];
		self::invokePrivate(Util::class, 'scripts', [[]]);
		self::invokePrivate(Util::class, 'scriptDeps', [[]]);
	}

	public function testAddScript(): void {
		Util::addScript('first', 'myFirstJSFile');
		Util::addScript('core', 'myFancyJSFile1');
		Util::addScript('files', 'myFancyJSFile2', 'core');
		Util::addScript('myApp5', 'myApp5JSFile', 'myApp2');
		Util::addScript('myApp', 'myFancyJSFile3');
		Util::addScript('core', 'myFancyJSFile4');
		// after itself
		Util::addScript('core', 'myFancyJSFile5', 'core');
		// add duplicate
		Util::addScript('core', 'myFancyJSFile1');
		// dependency chain
		Util::addScript('myApp4', 'myApp4JSFile', 'myApp3');
		Util::addScript('myApp3', 'myApp3JSFile', 'myApp2');
		Util::addScript('myApp2', 'myApp2JSFile', 'myApp');
		Util::addScript('core', 'common');
		Util::addScript('core', 'main');

		$scripts = Util::getScripts();

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
			'core/js/common',
			'core/js/main',
			'core/js/myFancyJSFile1',
			'core/js/myFancyJSFile4',
			'core/js/myFancyJSFile5',
			'first/l10n/en',
			'first/js/myFirstJSFile',
			'files/l10n/en',
			'files/js/myFancyJSFile2',
			'myApp/l10n/en',
			'myApp/js/myFancyJSFile3',
			'myApp2/l10n/en',
			'myApp2/js/myApp2JSFile',
			'myApp5/l10n/en',
			'myApp5/js/myApp5JSFile',
			'myApp3/l10n/en',
			'myApp3/js/myApp3JSFile',
			'myApp4/l10n/en',
			'myApp4/js/myApp4JSFile',
		];
		foreach ($scripts as $script) {
			$this->assertContains($script, $scripts);
		}
	}

	public function testAddScriptCircularDependency(): void {
		Util::addScript('circular', 'file1', 'dependency');
		Util::addScript('dependency', 'file2', 'circular');

		$scripts = Util::getScripts();
		$this->assertContains('circular/js/file1', $scripts);
		$this->assertContains('dependency/js/file2', $scripts);
	}

	public function testAddTranslations(): void {
		Util::addTranslations('appId', 'de');

		$this->assertEquals([
			'appId/l10n/de'
		], Util::getScripts());
		$this->assertEquals([], \OC_Util::$styles);
	}

	public function testAddStyle(): void {
		Util::addStyle('core', 'myFancyCSSFile1');
		Util::addStyle('myApp', 'myFancyCSSFile2');
		Util::addStyle('core', 'myFancyCSSFile0', true);
		Util::addStyle('core', 'myFancyCSSFile10', true);
		// add duplicate
		Util::addStyle('core', 'myFancyCSSFile1');

		$this->assertEquals([], Util::getScripts());
		$this->assertEquals([
			'core/css/myFancyCSSFile10',
			'core/css/myFancyCSSFile0',
			'core/css/myFancyCSSFile1',
			'myApp/css/myFancyCSSFile2',
		], \OC_Util::$styles);
	}

	public function testAddVendorStyle(): void {
		\OC_Util::addVendorStyle('core', 'myFancyCSSFile1');
		\OC_Util::addVendorStyle('myApp', 'myFancyCSSFile2');
		\OC_Util::addVendorStyle('core', 'myFancyCSSFile0', true);
		\OC_Util::addVendorStyle('core', 'myFancyCSSFile10', true);
		// add duplicate
		\OC_Util::addVendorStyle('core', 'myFancyCSSFile1');

		$this->assertEquals([], Util::getScripts());
		$this->assertEquals([
			'core/vendor/myFancyCSSFile10',
			'core/vendor/myFancyCSSFile0',
			'core/vendor/myFancyCSSFile1',
			'myApp/vendor/myFancyCSSFile2',
		], \OC_Util::$styles);
	}

	public function testShortenMultibyteString(): void {
		$this->assertEquals('Short nuff', Util::shortenMultibyteString('Short nuff', 255));
		$this->assertEquals('ABC', Util::shortenMultibyteString('ABCDEF', 3));
		// each of the characters is 12 bytes
		$this->assertEquals('🙈', Util::shortenMultibyteString('🙈🙊🙉', 16, 2));
	}
}
