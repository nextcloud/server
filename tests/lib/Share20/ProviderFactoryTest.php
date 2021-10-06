<?php
/**
 * @author Michiel de Jong <michiel@unhosted.org>
 *
 * @copyright Copyright (c) 2022, Nextcloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test\Share20;

use OCP\IServerContainer;

const EXAMPLE_SHARE_TYPE = 123;
const UNKNOWN_SHARE_TYPE = 456;

class ExampleProvider {
	public function isShareTypeSupported($shareType) {
		return ($shareType == EXAMPLE_SHARE_TYPE);
	}
	public function identifier() {
		return "example";	
	}
}

/**
 * Class ProviderFactoryTest
 *
 * @package Test\Share20
 */
class ProviderFactoryTest extends \Test\TestCase {

	/** @var \OCP\IServerContainer|\PHPUnit\Framework\MockObject\MockObject */
	protected $serverContainer;
	/** @var \OCP\Share20\ProviderFactory */
	protected $factory;

	/** @var ExampleProvider */
	protected $dynamicProvider;

	protected function setUp(): void {
		$this->dynamicProvider = new ExampleProvider();
		$this->serverContainer = $this->createMock(IServerContainer::class);
		$this->serverContainer->method('get')
			->with(ExampleProvider::class)
			->willReturn($this->dynamicProvider);
		$this->factory = new \OC\Share20\ProviderFactory($this->serverContainer);
		$this->factory->registerProvider(ExampleProvider::class);
	}


	public function testDynamicProvider() {
		$provider = $this->factory->getProviderForType(EXAMPLE_SHARE_TYPE);
		$this->assertEquals($provider, $this->dynamicProvider);
	}

	public function testUnknownType() {
		$this->expectExceptionMessage('No share provider for share type 456');
		$provider = $this->factory->getProviderForType(UNKNOWN_SHARE_TYPE);
	}
}
