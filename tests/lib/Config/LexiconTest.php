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
use OC\Config\ConfigManager;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use OCP\Server;
use Test\TestCase;

/**
 * Class UserPreferencesTest
 *
 * @group DB
 *
 * @package Test
 */
class LexiconTest extends TestCase {
	/** @var AppConfig */
	private IAppConfig $appConfig;
	private IUserConfig $userConfig;
	private ConfigManager $configManager;

	protected function setUp(): void {
		parent::setUp();

		$bootstrapCoordinator = Server::get(Coordinator::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestConfigLexicon_I::APPID, TestConfigLexicon_I::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestConfigLexicon_N::APPID, TestConfigLexicon_N::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestConfigLexicon_W::APPID, TestConfigLexicon_W::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestConfigLexicon_E::APPID, TestConfigLexicon_E::class);

		$this->appConfig = Server::get(IAppConfig::class);
		$this->userConfig = Server::get(IUserConfig::class);
		$this->configManager = Server::get(ConfigManager::class);
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
		$this->assertSame('', $this->userConfig->getValueString('user1', TestConfigLexicon_E::APPID, 'key5', ''));
	}

	public function testUserLexiconGetException() {
		$this->expectException(UnknownKeyException::class);
		$this->userConfig->getValueString('user1', TestConfigLexicon_E::APPID, 'key_exception');
	}

	public function testAppConfigLexiconRenameSetNewValue() {
		$this->assertSame(12345, $this->appConfig->getValueInt(TestConfigLexicon_I::APPID, 'key3', 123));
		$this->appConfig->setValueInt(TestConfigLexicon_I::APPID, 'old_key3', 994);
		$this->assertSame(994, $this->appConfig->getValueInt(TestConfigLexicon_I::APPID, 'key3', 123));
	}

	public function testAppConfigLexiconRenameSetOldValuePreMigration() {
		$this->appConfig->ignoreLexiconAliases(true);
		$this->appConfig->setValueInt(TestConfigLexicon_I::APPID, 'old_key3', 993);
		$this->appConfig->ignoreLexiconAliases(false);
		$this->assertSame(12345, $this->appConfig->getValueInt(TestConfigLexicon_I::APPID, 'key3', 123));
	}

	public function testAppConfigLexiconRenameSetOldValuePostMigration() {
		$this->appConfig->ignoreLexiconAliases(true);
		$this->appConfig->setValueInt(TestConfigLexicon_I::APPID, 'old_key3', 994);
		$this->appConfig->ignoreLexiconAliases(false);
		$this->configManager->migrateConfigLexiconKeys(TestConfigLexicon_I::APPID);
		$this->assertSame(994, $this->appConfig->getValueInt(TestConfigLexicon_I::APPID, 'key3', 123));
	}

	public function testAppConfigLexiconRenameGetNewValue() {
		$this->appConfig->setValueInt(TestConfigLexicon_I::APPID, 'key3', 981);
		$this->assertSame(981, $this->appConfig->getValueInt(TestConfigLexicon_I::APPID, 'old_key3', 123));
	}

	public function testAppConfigLexiconRenameGetOldValuePreMigration() {
		$this->appConfig->ignoreLexiconAliases(true);
		$this->appConfig->setValueInt(TestConfigLexicon_I::APPID, 'key3', 984);
		$this->assertSame(123, $this->appConfig->getValueInt(TestConfigLexicon_I::APPID, 'old_key3', 123));
		$this->appConfig->ignoreLexiconAliases(false);
	}

	public function testAppConfigLexiconRenameGetOldValuePostMigration() {
		$this->appConfig->ignoreLexiconAliases(true);
		$this->appConfig->setValueInt(TestConfigLexicon_I::APPID, 'key3', 987);
		$this->appConfig->ignoreLexiconAliases(false);
		$this->configManager->migrateConfigLexiconKeys(TestConfigLexicon_I::APPID);
		$this->assertSame(987, $this->appConfig->getValueInt(TestConfigLexicon_I::APPID, 'old_key3', 123));
	}

	public function testAppConfigLexiconRenameInvertBoolean() {
		$this->appConfig->ignoreLexiconAliases(true);
		$this->appConfig->setValueBool(TestConfigLexicon_I::APPID, 'old_key4', true);
		$this->appConfig->ignoreLexiconAliases(false);
		$this->assertSame(true, $this->appConfig->getValueBool(TestConfigLexicon_I::APPID, 'key4'));
		$this->configManager->migrateConfigLexiconKeys(TestConfigLexicon_I::APPID);
		$this->assertSame(false, $this->appConfig->getValueBool(TestConfigLexicon_I::APPID, 'key4'));
	}

	public function testAppConfigOnSetEdit() {
		$this->appConfig->setValueInt(TestConfigLexicon_I::APPID, 'key5', 42);
		$this->assertSame(52, $this->appConfig->getValueInt(TestConfigLexicon_I::APPID, 'key5'));
	}

	public function testAppConfigOnSetIgnore() {
		$this->appConfig->setValueInt(TestConfigLexicon_I::APPID, 'key5', 142);
		$this->assertSame(12, $this->appConfig->getValueInt(TestConfigLexicon_I::APPID, 'key5'));
	}

	public function testUserConfigOnSetEdit() {
		$this->userConfig->setValueInt('user1', TestConfigLexicon_I::APPID, 'key5', 42);
		$this->assertSame(32, $this->userConfig->getValueInt('user1', TestConfigLexicon_I::APPID, 'key5'));
	}

	public function testUserConfigOnSetIgnore() {
		$this->userConfig->setValueInt('user1', TestConfigLexicon_I::APPID, 'key5', 142);
		$this->assertSame(12, $this->userConfig->getValueInt('user1', TestConfigLexicon_I::APPID, 'key5'));
	}

	public function testAppConfigInitialize() {
		$this->assertSame('random_string', $this->appConfig->getValueString(TestConfigLexicon_I::APPID, 'key6'));
	}

	public function testUserConfigInitialize() {
		$this->assertSame('random_string', $this->userConfig->getValueString('user1', TestConfigLexicon_I::APPID, 'key6'));
	}

}
