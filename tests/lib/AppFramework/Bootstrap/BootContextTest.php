<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace lib\AppFramework\Bootstrap;

use OC\AppFramework\Bootstrap\BootContext;
use OCP\AppFramework\IAppContainer;
use OCP\IServerContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BootContextTest extends TestCase {

	/** @var IAppContainer|MockObject */
	private $appContainer;

	/** @var BootContext */
	private $context;

	protected function setUp(): void {
		parent::setUp();

		$this->appContainer = $this->createMock(IAppContainer::class);

		$this->context = new BootContext(
			$this->appContainer
		);
	}

	public function testGetAppContainer(): void {
		$container = $this->context->getAppContainer();

		$this->assertSame($this->appContainer, $container);
	}

	public function testGetServerContainer(): void {
		$serverContainer = $this->createMock(IServerContainer::class);
		$this->appContainer->method('get')
			->with(IServerContainer::class)
			->willReturn($serverContainer);

		$container = $this->context->getServerContainer();

		$this->assertSame($serverContainer, $container);
	}
}
