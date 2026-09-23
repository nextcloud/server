<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore;

use OC\App\AppStore\AppStoreLinkVisibility;
use OC\Core\AppInfo\ConfigLexicon;
use OCP\Config\Lexicon\Entry;
use OCP\Config\Lexicon\Preset;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\Support\Subscription\IRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AppStoreLinkVisibilityTest extends TestCase {
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private IRegistry&MockObject $registry;
	private AppStoreLinkVisibility $visibility;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->registry = $this->createMock(IRegistry::class);

		$this->visibility = new AppStoreLinkVisibility(
			$this->config,
			$this->appConfig,
			$this->registry,
		);
	}

	/**
	 * @param bool $stored whether an admin set the config key
	 * @param bool $value the stored value, or the lexicon default when $stored is false
	 */
	private function arrange(bool $stored, bool $value, bool $appStoreEnabled, bool $subscription): void {
		$this->appConfig->method('hasKey')
			->with('core', ConfigLexicon::APPSTORE_LINK_SHOWN)
			->willReturn($stored);
		$this->appConfig->method('getValueBool')
			->with('core', ConfigLexicon::APPSTORE_LINK_SHOWN)
			->willReturn($value);
		$this->config->method('getSystemValueBool')
			->with('appstoreenabled', true)
			->willReturn($appStoreEnabled);
		$this->registry->method('delegateHasValidSubscription')
			->willReturn($subscription);
	}

	public static function dataBool(): array {
		return [
			'shown' => [true],
			'hidden' => [false],
		];
	}

	#[DataProvider('dataBool')]
	public function testStoredValueWinsOverSubscriptionAndDisabledAppStore(bool $stored): void {
		$this->arrange(stored: true, value: $stored, appStoreEnabled: false, subscription: true);

		self::assertSame($stored, $this->visibility->isShownToUsers());
	}

	public function testHiddenWhenAppStoreIsDisabled(): void {
		$this->arrange(stored: false, value: true, appStoreEnabled: false, subscription: false);

		self::assertFalse($this->visibility->isShownToUsers());
	}

	public function testHiddenWhenSubscriptionIsAvailable(): void {
		$this->arrange(stored: false, value: true, appStoreEnabled: true, subscription: true);

		self::assertFalse($this->visibility->isShownToUsers());
	}

	#[DataProvider('dataBool')]
	public function testUnstoredKeyReturnsTheLexiconDefault(bool $default): void {
		$this->arrange(stored: false, value: $default, appStoreEnabled: true, subscription: false);

		self::assertSame($default, $this->visibility->isShownToUsers());
	}

	public static function dataLexiconPreset(): array {
		return [
			// Existing instances run without a preset and must keep the link.
			[Preset::NONE, '1'],
			[Preset::FAMILY, '1'],
			[Preset::UNIVERSITY, '0'],
		];
	}

	/**
	 * The lexicon default {@see AppStoreLinkVisibility} falls back to is preset
	 * dependent. A fresh entry is built per case because {@see Entry::getDefault()}
	 * memoizes the first default it is asked for.
	 */
	#[DataProvider('dataLexiconPreset')]
	public function testLexiconPresetDefault(Preset $preset, string $expected): void {
		self::assertSame($expected, $this->lexiconEntry()->getDefault($preset));
	}

	private function lexiconEntry(): Entry {
		foreach ((new ConfigLexicon())->getAppConfigs() as $entry) {
			if ($entry->getKey() === ConfigLexicon::APPSTORE_LINK_SHOWN) {
				return $entry;
			}
		}

		self::fail('No lexicon entry for ' . ConfigLexicon::APPSTORE_LINK_SHOWN);
	}
}
