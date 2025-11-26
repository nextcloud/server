<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Tests\lib\Config;

use OC\AppConfig;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\Config\ConfigManager;
use OC\Config\PresetManager;
use OCP\App\IAppManager;
use OCP\Config\Exceptions\TypeConflictException;
use OCP\Config\Exceptions\UnknownKeyException;
use OCP\Config\IUserConfig;
use OCP\Config\Lexicon\Preset;
use OCP\Exceptions\AppConfigTypeConflictException;
use OCP\Exceptions\AppConfigUnknownKeyException;
use OCP\IAppConfig;
use OCP\Server;
use Test\TestCase;

/**
 * Class UserPreferencesTest
 *
 *
 * @package Test
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class LexiconTest extends TestCase {
	/** @var AppConfig */
	private IAppConfig $appConfig;
	private IUserConfig $userConfig;
	private ConfigManager $configManager;
	private PresetManager $presetManager;
	private IAppManager $appManager;

	protected function setUp(): void {
		parent::setUp();

		$bootstrapCoordinator = Server::get(Coordinator::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestConfigLexicon_I::APPID, TestConfigLexicon_I::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestLexicon_N::APPID, TestLexicon_N::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestLexicon_W::APPID, TestLexicon_W::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestLexicon_E::APPID, TestLexicon_E::class);

		$this->appConfig = Server::get(IAppConfig::class);
		$this->userConfig = Server::get(IUserConfig::class);
		$this->configManager = Server::get(ConfigManager::class);
		$this->presetManager = Server::get(PresetManager::class);
		$this->appManager = Server::get(IAppManager::class);
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->appConfig->deleteApp(TestConfigLexicon_I::APPID);
		$this->appConfig->deleteApp(TestLexicon_N::APPID);
		$this->appConfig->deleteApp(TestLexicon_W::APPID);
		$this->appConfig->deleteApp(TestLexicon_E::APPID);
		$this->appConfig->deleteApp(TestLexicon_UserIndexed::APPID);
		$this->appConfig->deleteApp(TestLexicon_UserIndexedRemove::APPID);

		$this->userConfig->deleteApp(TestConfigLexicon_I::APPID);
		$this->userConfig->deleteApp(TestLexicon_N::APPID);
		$this->userConfig->deleteApp(TestLexicon_W::APPID);
		$this->userConfig->deleteApp(TestLexicon_E::APPID);
		$this->userConfig->deleteApp(TestLexicon_UserIndexed::APPID);
		$this->userConfig->deleteApp(TestLexicon_UserIndexedRemove::APPID);
	}

	public function testAppLexiconSetCorrect() {
		$this->assertSame(true, $this->appConfig->setValueString(TestLexicon_E::APPID, 'key1', 'new_value'));
		$this->assertSame(true, $this->appConfig->isLazy(TestLexicon_E::APPID, 'key1'));
		$this->assertSame(true, $this->appConfig->isSensitive(TestLexicon_E::APPID, 'key1'));
		$this->appConfig->deleteKey(TestLexicon_E::APPID, 'key1');
	}

	public function testAppLexiconGetCorrect() {
		$this->assertSame('abcde', $this->appConfig->getValueString(TestLexicon_E::APPID, 'key1', 'default'));
	}

	public function testAppLexiconSetIncorrectValueType() {
		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->setValueInt(TestLexicon_E::APPID, 'key1', -1);
	}

	public function testAppLexiconGetIncorrectValueType() {
		$this->expectException(AppConfigTypeConflictException::class);
		$this->appConfig->getValueInt(TestLexicon_E::APPID, 'key1');
	}

	public function testAppLexiconIgnore() {
		$this->appConfig->setValueString(TestConfigLexicon_I::APPID, 'key_ignore', 'new_value');
		$this->assertSame('new_value', $this->appConfig->getValueString(TestConfigLexicon_I::APPID, 'key_ignore', ''));
	}

	public function testAppLexiconNotice() {
		$this->appConfig->setValueString(TestLexicon_N::APPID, 'key_notice', 'new_value');
		$this->assertSame('new_value', $this->appConfig->getValueString(TestLexicon_N::APPID, 'key_notice', ''));
	}

	public function testAppLexiconWarning() {
		$this->appConfig->setValueString(TestLexicon_W::APPID, 'key_warning', 'new_value');
		$this->assertSame('', $this->appConfig->getValueString(TestLexicon_W::APPID, 'key_warning', ''));
	}

	public function testAppLexiconSetException() {
		$this->expectException(AppConfigUnknownKeyException::class);
		$this->appConfig->setValueString(TestLexicon_E::APPID, 'key_exception', 'new_value');
		$this->assertSame('', $this->appConfig->getValueString(TestLexicon_E::APPID, 'key3', ''));
	}

	public function testAppLexiconGetException() {
		$this->expectException(AppConfigUnknownKeyException::class);
		$this->appConfig->getValueString(TestLexicon_E::APPID, 'key_exception');
	}

	public function testUserLexiconSetCorrect() {
		$this->assertSame(true, $this->userConfig->setValueString('user1', TestLexicon_E::APPID, 'key1', 'new_value'));
		$this->assertSame(true, $this->userConfig->isLazy('user1', TestLexicon_E::APPID, 'key1'));
		$this->assertSame(true, $this->userConfig->isSensitive('user1', TestLexicon_E::APPID, 'key1'));
		$this->userConfig->deleteKey(TestLexicon_E::APPID, 'key1');
	}

	public function testUserLexiconGetCorrect() {
		$this->assertSame('abcde', $this->userConfig->getValueString('user1', TestLexicon_E::APPID, 'key1', 'default'));
	}

	public function testUserLexiconSetIncorrectValueType() {
		$this->expectException(TypeConflictException::class);
		$this->userConfig->setValueInt('user1', TestLexicon_E::APPID, 'key1', -1);
	}

	public function testUserLexiconGetIncorrectValueType() {
		$this->expectException(TypeConflictException::class);
		$this->userConfig->getValueInt('user1', TestLexicon_E::APPID, 'key1');
	}

	public function testUserLexiconIgnore() {
		$this->userConfig->setValueString('user1', TestConfigLexicon_I::APPID, 'key_ignore', 'new_value');
		$this->assertSame('new_value', $this->userConfig->getValueString('user1', TestConfigLexicon_I::APPID, 'key_ignore', ''));
	}

	public function testUserLexiconNotice() {
		$this->userConfig->setValueString('user1', TestLexicon_N::APPID, 'key_notice', 'new_value');
		$this->assertSame('new_value', $this->userConfig->getValueString('user1', TestLexicon_N::APPID, 'key_notice', ''));
	}

	public function testUserLexiconWarning() {
		$this->userConfig->setValueString('user1', TestLexicon_W::APPID, 'key_warning', 'new_value');
		$this->assertSame('', $this->userConfig->getValueString('user1', TestLexicon_W::APPID, 'key_warning', ''));
	}

	public function testUserLexiconSetException() {
		$this->expectException(UnknownKeyException::class);
		$this->userConfig->setValueString('user1', TestLexicon_E::APPID, 'key_exception', 'new_value');
		$this->assertSame('', $this->userConfig->getValueString('user1', TestLexicon_E::APPID, 'key5', ''));
	}

	public function testUserLexiconGetException() {
		$this->expectException(UnknownKeyException::class);
		$this->userConfig->getValueString('user1', TestLexicon_E::APPID, 'key_exception');
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

	public function testAppConfigLexiconPreset() {
		$this->presetManager->setLexiconPreset(Preset::FAMILY);
		$this->assertSame('family', $this->appConfig->getValueString(TestLexicon_E::APPID, 'key3'));
	}

	public function testAppConfigLexiconPresets() {
		$this->presetManager->setLexiconPreset(Preset::MEDIUM);
		$this->assertSame('club+medium', $this->appConfig->getValueString(TestLexicon_E::APPID, 'key3'));
		$this->presetManager->setLexiconPreset(Preset::FAMILY);
		$this->assertSame('family', $this->appConfig->getValueString(TestLexicon_E::APPID, 'key3'));
	}

	public function testUserConfigLexiconPreset() {
		$this->presetManager->setLexiconPreset(Preset::FAMILY);
		$this->assertSame('family', $this->userConfig->getValueString('user1', TestLexicon_E::APPID, 'key3'));
	}

	public function testUserConfigLexiconPresets() {
		$this->presetManager->setLexiconPreset(Preset::MEDIUM);
		$this->assertSame('club+medium', $this->userConfig->getValueString('user1', TestLexicon_E::APPID, 'key3'));
		$this->presetManager->setLexiconPreset(Preset::FAMILY);
		$this->assertSame('family', $this->userConfig->getValueString('user1', TestLexicon_E::APPID, 'key3'));
	}

	public function testLexiconIndexedUpdate() {
		$this->userConfig->setValueString('user1', TestLexicon_UserIndexed::APPID, 'key1', 'abcd');
		$this->userConfig->setValueString('user2', TestLexicon_UserIndexed::APPID, 'key1', '1234', flags: 64);
		$this->userConfig->setValueString('user3', TestLexicon_UserIndexed::APPID, 'key1', 'qwer', flags: IUserConfig::FLAG_INDEXED);
		$this->userConfig->setValueString('user4', TestLexicon_UserIndexed::APPID, 'key1', 'uiop', flags: 64 | IUserConfig::FLAG_INDEXED);

		$bootstrapCoordinator = Server::get(Coordinator::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestLexicon_UserIndexed::APPID, TestLexicon_UserIndexed::class);
		$this->userConfig->clearCacheAll();

		$this->configManager->updateLexiconEntries(TestLexicon_UserIndexed::APPID);

		$this->assertTrue($this->userConfig->isIndexed('user1', TestLexicon_UserIndexed::APPID, 'key1'));
		$this->assertTrue($this->userConfig->isIndexed('user2', TestLexicon_UserIndexed::APPID, 'key1'));
		$this->assertTrue($this->userConfig->isIndexed('user3', TestLexicon_UserIndexed::APPID, 'key1'));
		$this->assertTrue($this->userConfig->isIndexed('user4', TestLexicon_UserIndexed::APPID, 'key1'));

		$this->assertSame(2, $this->userConfig->getValueFlags('user1', TestLexicon_UserIndexed::APPID, 'key1'));
		$this->assertSame(66, $this->userConfig->getValueFlags('user2', TestLexicon_UserIndexed::APPID, 'key1'));
		$this->assertSame(2, $this->userConfig->getValueFlags('user3', TestLexicon_UserIndexed::APPID, 'key1'));
		$this->assertSame(66, $this->userConfig->getValueFlags('user4', TestLexicon_UserIndexed::APPID, 'key1'));
	}

	public function testLexiconIndexedUpdateRemove() {
		$this->userConfig->setValueString('user1', TestLexicon_UserIndexedRemove::APPID, 'key1', 'abcd');
		$this->userConfig->setValueString('user2', TestLexicon_UserIndexedRemove::APPID, 'key1', '1234', flags: 64);
		$this->userConfig->setValueString('user3', TestLexicon_UserIndexedRemove::APPID, 'key1', 'qwer', flags: IUserConfig::FLAG_INDEXED);
		$this->userConfig->setValueString('user4', TestLexicon_UserIndexedRemove::APPID, 'key1', 'uiop', flags: 64 | IUserConfig::FLAG_INDEXED);

		$bootstrapCoordinator = Server::get(Coordinator::class);
		$bootstrapCoordinator->getRegistrationContext()?->registerConfigLexicon(TestLexicon_UserIndexedRemove::APPID, TestLexicon_UserIndexedRemove::class);
		$this->userConfig->clearCacheAll();

		$this->configManager->updateLexiconEntries(TestLexicon_UserIndexedRemove::APPID);

		$this->assertFalse($this->userConfig->isIndexed('user1', TestLexicon_UserIndexedRemove::APPID, 'key1'));
		$this->assertFalse($this->userConfig->isIndexed('user2', TestLexicon_UserIndexedRemove::APPID, 'key1'));
		$this->assertFalse($this->userConfig->isIndexed('user3', TestLexicon_UserIndexedRemove::APPID, 'key1'));
		$this->assertFalse($this->userConfig->isIndexed('user4', TestLexicon_UserIndexedRemove::APPID, 'key1'));

		$this->assertSame(0, $this->userConfig->getValueFlags('user1', TestLexicon_UserIndexedRemove::APPID, 'key1'));
		$this->assertSame(64, $this->userConfig->getValueFlags('user2', TestLexicon_UserIndexedRemove::APPID, 'key1'));
		$this->assertSame(0, $this->userConfig->getValueFlags('user3', TestLexicon_UserIndexedRemove::APPID, 'key1'));
		$this->assertSame(64, $this->userConfig->getValueFlags('user4', TestLexicon_UserIndexedRemove::APPID, 'key1'));
	}
}
