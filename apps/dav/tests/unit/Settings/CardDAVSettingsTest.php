<?php
/**
 * @copyright 2019, Thomas Citharel <tcit@tcit.fr>
 *
 * @author Thomas Citharel <tcit.fr@tcit.fr>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Tests\Unit\DAV\Settings;

use OCA\DAV\Settings\CardDAVSettings;
use OCP\IConfig;
use Test\TestCase;

class CardDAVSettingsTest extends TestCase {

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;

	/** @var CardDAVSettings */
	private $settings;

	public function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->settings = new CardDAVSettings($this->config);
	}

	public function testGetForm() {
		$result = $this->settings->getForm();

		$this->assertInstanceOf('OCP\AppFramework\Http\TemplateResponse', $result);
	}

	public function testGetSection() {
		$this->assertEquals('groupware', $this->settings->getSection());
	}

	public function testGetPriority() {
		$this->assertEquals(10, $this->settings->getPriority());
	}
}
