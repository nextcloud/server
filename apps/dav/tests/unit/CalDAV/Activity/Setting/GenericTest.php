<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Activity\Setting;

use OCA\DAV\CalDAV\Activity\Setting\Calendar;
use OCA\DAV\CalDAV\Activity\Setting\Event;
use OCA\DAV\CalDAV\Activity\Setting\Todo;
use OCP\Activity\ISetting;
use OCP\Server;
use Test\TestCase;

class GenericTest extends TestCase {
	public static function dataSettings(): array {
		return [
			[Calendar::class],
			[Event::class],
			[Todo::class],
		];
	}

	/**
	 * @dataProvider dataSettings
	 */
	public function testImplementsInterface(string $settingClass): void {
		$setting = Server::get($settingClass);
		$this->assertInstanceOf(ISetting::class, $setting);
	}

	/**
	 * @dataProvider dataSettings
	 */
	public function testGetIdentifier(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = Server::get($settingClass);
		$this->assertIsString($setting->getIdentifier());
	}

	/**
	 * @dataProvider dataSettings
	 */
	public function testGetName(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = Server::get($settingClass);
		$this->assertIsString($setting->getName());
	}

	/**
	 * @dataProvider dataSettings
	 */
	public function testGetPriority(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = Server::get($settingClass);
		$priority = $setting->getPriority();
		$this->assertIsInt($setting->getPriority());
		$this->assertGreaterThanOrEqual(0, $priority);
		$this->assertLessThanOrEqual(100, $priority);
	}

	/**
	 * @dataProvider dataSettings
	 */
	public function testCanChangeStream(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = Server::get($settingClass);
		$this->assertIsBool($setting->canChangeStream());
	}

	/**
	 * @dataProvider dataSettings
	 */
	public function testIsDefaultEnabledStream(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = Server::get($settingClass);
		$this->assertIsBool($setting->isDefaultEnabledStream());
	}

	/**
	 * @dataProvider dataSettings
	 */
	public function testCanChangeMail(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = Server::get($settingClass);
		$this->assertIsBool($setting->canChangeMail());
	}

	/**
	 * @dataProvider dataSettings
	 */
	public function testIsDefaultEnabledMail(string $settingClass): void {
		/** @var ISetting $setting */
		$setting = Server::get($settingClass);
		$this->assertIsBool($setting->isDefaultEnabledMail());
	}
}
