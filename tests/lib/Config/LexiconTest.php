<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Tests\lib\Config;

use NCU\Config\Exceptions\TypeConflictException;
use NCU\Config\Exceptions\UnknownKeyException;
use NCU\Config\IUserConfig;
use OC\AppConfig;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Config\UserConfig;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Security\ICrypto;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class UserPreferencesTest
 *
 * @group DB
 *
 * @package Test
 */
class LexiconTest extends TestCase {
	private IAppConfig $appConfig;
	private IUserConfig $userConfig;

	protected function setUp(): void {
		parent::setUp();

		$bootstrapCoordinator = \OCP\Server::get(Coordinator::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestConfigLexicon_I::APPID, TestConfigLexicon_I::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestConfigLexicon_N::APPID, TestConfigLexicon_N::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestConfigLexicon_W::APPID, TestConfigLexicon_W::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestConfigLexicon_E::APPID, TestConfigLexicon_E::class);

		$this->appConfig = Server::get(IAppConfig::class);
		$this->userConfig = Server::get(IUserConfig::class);
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->appConfig->deleteApp(TestConfigLexicon_I::APPID);
		$this->appConfig->deleteApp(TestConfigLexicon_N::APPID);
		$this->appConfig->deleteApp(TestConfigLexicon_W::APPID);
		$this->appConfig->deleteApp(TestConfigLexicon_E::APPID);

		$this->userConfig->deleteApp(TestConfigLexicon_I::APPID);
		$this->userConfig->deleteApp(TestConfigLexicon_N::APPID);
		$this->userConfig->deleteApp(TestConfigLexicon_W::APPID);
		$this->userConfig->deleteApp(TestConfigLexicon_E::APPID);
	}

	public function testAppLexiconSetCorrect() {
		$this->assertSame(true, $this->appConfig->setValueString(TestConfigLexicon_E::APPID, 'key1', 'new_value'));
		$this->assertSame(true, $this->appConfig->isLazy(TestConfigLexicon_E::APPID, 'key1'));
		$this->assertSame(true, $this->appConfig->isSensitive(TestConfigLexicon_E::APPID, 'key1'));
		$this->appConfig->deleteKey(TestConfigLexicon_E::APPID, 'key1');
	}

	public function testAppConfigMigrationToLazy() {
		$app = TestConfigLexicon_Migration::APPID . '_app';
		// to avoid filling cache with an empty Lexicon, we use a new IAppConfig
		$appConfig = new AppConfig(
			Server::get(IDBConnection::class),
			Server::get(LoggerInterface::class),
			Server::get(ICrypto::class),
		);
		$this->assertSame(true, $appConfig->setValueString($app, 'key1', 'value1'));

		// confirm that key1 is not lazy, even after a refresh of the cache
		$appConfig->clearCache();
		$this->assertSame('value1', $appConfig->getValueString($app, 'key1'));
		$this->assertSame(true, array_key_exists('key1', $appConfig->statusCache()['fastCache'][$app]));

		// loading new Lexicon that set key1 as lazy
		$bootstrapCoordinator = \OCP\Server::get(Coordinator::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon($app, TestConfigLexicon_Migration::class);

		// caching
		$this->assertSame('default0', $this->appConfig->getValueString($app, 'key0'));
		// still not lazy
		$this->assertSame(true, array_key_exists('key1', $this->appConfig->statusCache()['fastCache'][$app]));
		// trigger update of status
		$this->assertSame('value1', $this->appConfig->getValueString($app, 'key1'));
		// should be lazy now
		$this->assertSame(true, array_key_exists('key1', $this->appConfig->statusCache()['lazyCache'][$app]));

		$this->appConfig->clearCache();
		$this->assertSame('default0', $this->appConfig->getValueString($app, 'key0'));
		// definitively lazy
		$this->assertSame(false, array_key_exists($app, $this->appConfig->statusCache()['fastCache']));

		$this->appConfig->deleteKey($app, 'key1');
	}

	public function testUserConfigMigrationToLazy() {
		$app = TestConfigLexicon_Migration::APPID . '_user';
		// to avoid filling cache with an empty Lexicon, we use a new IAppConfig
		$userConfig = new UserConfig(
			Server::get(IDBConnection::class),
			Server::get(IConfig::class),
			Server::get(LoggerInterface::class),
			Server::get(ICrypto::class),
		);
		$this->assertSame(true, $userConfig->setValueString('user1', $app, 'key1', 'value1'));

		// confirm that key1 is not lazy, even after a refresh of Lexicon
		$userConfig->clearCache('user1');
		$this->assertSame('value1', $userConfig->getValueString('user1', $app, 'key1'));
		$this->assertSame(true, array_key_exists('key1', $userConfig->statusCache()['fastCache']['user1'][$app]));

		// loading new Lexicon that set key1 as lazy
		$bootstrapCoordinator = \OCP\Server::get(Coordinator::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon($app, TestConfigLexicon_Migration::class);

		// caching
		$this->assertSame('default0', $this->userConfig->getValueString('user1', $app, 'key0'));
		// still not lazy
		$this->assertSame(true, array_key_exists('key1', $this->userConfig->statusCache()['fastCache']['user1'][$app]));
		// trigger update of status
		$this->assertSame('value1', $this->userConfig->getValueString('user1', $app, 'key1'));
		// should be lazy now
		$this->assertSame(true, array_key_exists('key1', $this->userConfig->statusCache()['lazyCache']['user1'][$app]));

		$this->userConfig->clearCache('user1');
		$this->assertSame('default0', $this->userConfig->getValueString('user1', $app, 'key0'));
		// definitively lazy
		$this->assertSame(false, array_key_exists($app, $this->userConfig->statusCache()['fastCache']['user1']));

		$this->userConfig->deleteKey($app, 'key1');
	}


	public function testAppLexiconGetCorrect() {
		$this->assertSame('abcde', $this->appConfig->getValueString(TestConfigLexicon_E::APPID, 'key1', 'default'));
	}

	public function testAppLexiconSetIncorrectValueType() {
		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->setValueInt(TestConfigLexicon_E::APPID, 'key1', -1);
	}

	public function testAppLexiconGetIncorrectValueType() {
		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->getValueInt(TestConfigLexicon_E::APPID, 'key1');
	}

	public function testAppLexiconIgnore() {
		$this->appConfig->setValueString(TestConfigLexicon_I::APPID, 'key_ignore', 'new_value');
		$this->assertSame('new_value', $this->appConfig->getValueString(TestConfigLexicon_I::APPID, 'key_ignore', ''));
	}

	public function testAppLexiconNotice() {
		$this->appConfig->setValueString(TestConfigLexicon_N::APPID, 'key_notice', 'new_value');
		$this->assertSame('new_value', $this->appConfig->getValueString(TestConfigLexicon_N::APPID, 'key_notice', ''));
	}

	public function testAppLexiconWarning() {
		$this->appConfig->setValueString(TestConfigLexicon_W::APPID, 'key_warning', 'new_value');
		$this->assertSame('', $this->appConfig->getValueString(TestConfigLexicon_W::APPID, 'key_warning', ''));
	}

	public function testAppLexiconSetException() {
		$this->expectException(AppConfigUnknownKeyException::class);
		$this->appConfig->setValueString(TestConfigLexicon_E::APPID, 'key_exception', 'new_value');
		$this->assertSame('', $this->appConfig->getValueString(TestConfigLexicon_E::APPID, 'key3', ''));
	}

	public function testAppLexiconGetException() {
		$this->expectException(AppConfigUnknownKeyException::class);
		$this->appConfig->getValueString(TestConfigLexicon_E::APPID, 'key_exception');
	}

	public function testUserLexiconSetCorrect() {
		$this->assertSame(true, $this->userConfig->setValueString('user1', TestConfigLexicon_E::APPID, 'key1', 'new_value'));
		$this->assertSame(true, $this->userConfig->isLazy('user1', TestConfigLexicon_E::APPID, 'key1'));
		$this->assertSame(true, $this->userConfig->isSensitive('user1', TestConfigLexicon_E::APPID, 'key1'));
		$this->userConfig->deleteKey(TestConfigLexicon_E::APPID, 'key1');
	}

	public function testUserLexiconGetCorrect() {
		$this->assertSame('abcde', $this->userConfig->getValueString('user1', TestConfigLexicon_E::APPID, 'key1', 'default'));
	}

	public function testUserLexiconSetIncorrectValueType() {
		$this->expectException(TypeConflictException::class);
		$this->userConfig->setValueInt('user1', TestConfigLexicon_E::APPID, 'key1', -1);
	}

	public function testUserLexiconGetIncorrectValueType() {
		$this->expectException(TypeConflictException::class);
		$this->userConfig->getValueInt('user1', TestConfigLexicon_E::APPID, 'key1');
	}

	public function testUserLexiconIgnore() {
		$this->userConfig->setValueString('user1', TestConfigLexicon_I::APPID, 'key_ignore', 'new_value');
		$this->assertSame('new_value', $this->userConfig->getValueString('user1', TestConfigLexicon_I::APPID, 'key_ignore', ''));
	}

	public function testUserLexiconNotice() {
		$this->userConfig->setValueString('user1', TestConfigLexicon_N::APPID, 'key_notice', 'new_value');
		$this->assertSame('new_value', $this->userConfig->getValueString('user1', TestConfigLexicon_N::APPID, 'key_notice', ''));
	}

	public function testUserLexiconWarning() {
		$this->userConfig->setValueString('user1', TestConfigLexicon_W::APPID, 'key_warning', 'new_value');
		$this->assertSame('', $this->userConfig->getValueString('user1', TestConfigLexicon_W::APPID, 'key_warning', ''));
	}

	public function testUserLexiconSetException() {
		$this->expectException(UnknownKeyException::class);
		$this->userConfig->setValueString('user1', TestConfigLexicon_E::APPID, 'key_exception', 'new_value');
		$this->assertSame('', $this->userConfig->getValueString('user1', TestConfigLexicon_E::APPID, 'key3', ''));
	}

	public function testUserLexiconGetException() {
		$this->expectException(UnknownKeyException::class);
		$this->userConfig->getValueString('user1', TestConfigLexicon_E::APPID, 'key_exception');
	}
}
