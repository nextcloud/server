<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\Tests\unit\CalDAV\Activity\Setting;

use OC;
use OCA\DAV\CalDAV\Activity\Setting\Calendar;
use OCA\DAV\CalDAV\Activity\Setting\Event;
use OCA\DAV\CalDAV\Activity\Setting\Todo;
use OCP\Activity\ISetting;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Test\TestCase;

class GenericTest extends TestCase {
	public function dataSettings(): array {
		return [
			[Calendar::class],
			[Event::class],
			[Todo::class],
		];
	}

	/**
	 * @dataProvider dataSettings
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testImplementsInterface(string $settingClass) {
		$setting = OC::$server->get($settingClass);
		$this->assertInstanceOf(ISetting::class, $setting);
	}

	/**
	 * @dataProvider dataSettings
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testGetIdentifier(string $settingClass) {
		/** @var ISetting $setting */
		$setting = OC::$server->get($settingClass);
		$this->assertIsString($setting->getIdentifier());
	}

	/**
	 * @dataProvider dataSettings
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testGetName(string $settingClass) {
		/** @var ISetting $setting */
		$setting = OC::$server->get($settingClass);
		$this->assertIsString($setting->getName());
	}

	/**
	 * @dataProvider dataSettings
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testGetPriority(string $settingClass) {
		/** @var ISetting $setting */
		$setting = OC::$server->get($settingClass);
		$priority = $setting->getPriority();
		$this->assertIsInt($setting->getPriority());
		$this->assertGreaterThanOrEqual(0, $priority);
		$this->assertLessThanOrEqual(100, $priority);
	}

	/**
	 * @dataProvider dataSettings
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testCanChangeStream(string $settingClass) {
		/** @var ISetting $setting */
		$setting = OC::$server->get($settingClass);
		$this->assertIsBool($setting->canChangeStream());
	}

	/**
	 * @dataProvider dataSettings
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testIsDefaultEnabledStream(string $settingClass) {
		/** @var ISetting $setting */
		$setting = OC::$server->get($settingClass);
		$this->assertIsBool($setting->isDefaultEnabledStream());
	}

	/**
	 * @dataProvider dataSettings
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testCanChangeMail(string $settingClass) {
		/** @var ISetting $setting */
		$setting = OC::$server->get($settingClass);
		$this->assertIsBool($setting->canChangeMail());
	}

	/**
	 * @dataProvider dataSettings
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testIsDefaultEnabledMail(string $settingClass) {
		/** @var ISetting $setting */
		$setting = OC::$server->get($settingClass);
		$this->assertIsBool($setting->isDefaultEnabledMail());
	}
}
