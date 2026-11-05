<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Profile;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Config\PresetManager;
use OC\Core\Db\ProfileConfigMapper;
use OC\KnownUser\KnownUserService;
use OC\Profile\ProfileManager;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
use OCP\Config\Lexicon\Preset;
use OCP\IConfig;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Profile\IProfileManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class ProfileManagerTest
 *
 * @package Test\Profile
 */
class ProfileManagerTest extends TestCase {

	private IAccountManager&MockObject $accountManager;
	private IAppManager&MockObject $appManager;
	private IConfig&MockObject $config;
	private ProfileConfigMapper&MockObject $configMapper;
	private ContainerInterface&MockObject $container;
	private KnownUserService&MockObject $knownUserService;
	private IFactory&MockObject $l10nFactory;
	private LoggerInterface&MockObject $logger;
	private Coordinator&MockObject $coordinator;
	private PresetManager&MockObject $presetManager;

	private ProfileManager $profileManager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->accountManager = $this->createMock(IAccountManager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->configMapper = $this->createMock(ProfileConfigMapper::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->knownUserService = $this->createMock(KnownUserService::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->coordinator = $this->createMock(Coordinator::class);
		$this->presetManager = $this->createMock(PresetManager::class);

		$this->profileManager = new ProfileManager(
			$this->accountManager,
			$this->appManager,
			$this->config,
			$this->configMapper,
			$this->container,
			$this->knownUserService,
			$this->l10nFactory,
			$this->logger,
			$this->coordinator,
			$this->presetManager
		);
	}

	public static function dataSetDefaultPropertyScopes(): array {
		return [
			[
				[], # returns just IProfileManager::DEFAULT_PROPERTY_VISIBILITY
				[
					IAccountManager::PROPERTY_ADDRESS => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
					IAccountManager::PROPERTY_AVATAR => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_BIOGRAPHY => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_DISPLAYNAME => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_HEADLINE => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_ORGANISATION => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_ROLE => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_EMAIL => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
					IAccountManager::PROPERTY_PHONE => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
					IAccountManager::PROPERTY_TWITTER => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_BLUESKY => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_WEBSITE => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_PRONOUNS => IProfileManager::VISIBILITY_SHOW,
				 ]
			],
			[
				[ # should overwrite their respective defaults
					IAccountManager::PROPERTY_AVATAR => IProfileManager::VISIBILITY_HIDE,
					IAccountManager::PROPERTY_ROLE => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
				],
				[
					IAccountManager::PROPERTY_ADDRESS => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
					IAccountManager::PROPERTY_AVATAR => IProfileManager::VISIBILITY_HIDE,
					IAccountManager::PROPERTY_BIOGRAPHY => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_DISPLAYNAME => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_HEADLINE => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_ORGANISATION => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_ROLE => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
					IAccountManager::PROPERTY_EMAIL => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
					IAccountManager::PROPERTY_PHONE => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
					IAccountManager::PROPERTY_TWITTER => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_BLUESKY => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_WEBSITE => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_PRONOUNS => IProfileManager::VISIBILITY_SHOW,
				]
			],
			[
				[
					IAccountManager::PROPERTY_BLUESKY => 'invalid', # should fall back to default
				],
				[
					IAccountManager::PROPERTY_ADDRESS => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
					IAccountManager::PROPERTY_AVATAR => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_BIOGRAPHY => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_DISPLAYNAME => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_HEADLINE => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_ORGANISATION => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_ROLE => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_EMAIL => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
					IAccountManager::PROPERTY_PHONE => IProfileManager::VISIBILITY_SHOW_USERS_ONLY,
					IAccountManager::PROPERTY_TWITTER => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_BLUESKY => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_WEBSITE => IProfileManager::VISIBILITY_SHOW,
					IAccountManager::PROPERTY_PRONOUNS => IProfileManager::VISIBILITY_SHOW,
				]
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataSetDefaultPropertyScopes')]
	public function testSetDefaultPropertyVisibility(array $propertyVisibilities, array $expected): void {
		$targetUser = $this->createMock(IUser::class);
		$this->invokePrivate($this->profileManager, 'sortedActions', [[]]);
		$this->presetManager->method('getLexiconPreset')->willReturn(Preset::NONE);
		$this->config->expects($this->once())->method('getSystemValue')->with('profile_manager.default_property_visibility', [])->willReturn($propertyVisibilities);

		$result = $this->invokePrivate($this->profileManager, 'getDefaultProfileConfig', [$targetUser, null]);

		foreach ($expected as $key => $expectedValue) {
			$resultVisibility = $result[$key]['visibility'];
			$this->assertEquals($expectedValue, $resultVisibility, "The result visibility doesn't follow the value set into the config or defaults correctly.");
		}
	}
}
