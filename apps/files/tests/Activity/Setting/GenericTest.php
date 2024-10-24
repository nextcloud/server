<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Tests\Activity\Setting;

use OCA\Files\Activity\Settings\FavoriteAction;
use OCA\Files\Activity\Settings\FileChanged;
use OCP\Activity\ISetting;
use Test\TestCase;

class GenericTest extends TestCase {
	public function dataSettings() {
		return [
			[FavoriteAction::class],
			[FileChanged::class],
			[FileChanged::class],
		];
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testImplementsInterface($settingClass): void {
		$setting = \OC::$server->query($settingClass);
		$this->assertInstanceOf(ISetting::class, $setting);
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testGetIdentifier($settingClass): void {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		$this->assertIsString($setting->getIdentifier());
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testGetName($settingClass): void {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		$this->assertIsString($setting->getName());
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testGetPriority($settingClass): void {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		$priority = $setting->getPriority();
		$this->assertIsInt($setting->getPriority());
		$this->assertGreaterThanOrEqual(0, $priority);
		$this->assertLessThanOrEqual(100, $priority);
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testCanChangeStream($settingClass): void {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		$this->assertIsBool($setting->canChangeStream());
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testIsDefaultEnabledStream($settingClass): void {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		$this->assertIsBool($setting->isDefaultEnabledStream());
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testCanChangeMail($settingClass): void {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		$this->assertIsBool($setting->canChangeMail());
	}

	/**
	 * @dataProvider dataSettings
	 * @param string $settingClass
	 */
	public function testIsDefaultEnabledMail($settingClass): void {
		/** @var ISetting $setting */
		$setting = \OC::$server->query($settingClass);
		$this->assertIsBool($setting->isDefaultEnabledMail());
	}
}
