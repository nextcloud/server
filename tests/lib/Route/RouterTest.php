<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Morris Jobke <hey@morrisjobke.de>
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

namespace Test\Route;

use OC\Route\Router;
use OCP\Diagnostics\IEventLogger;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class RouterTest
 *
 * @group RoutingWeirdness
 *
 * @package Test\Route
 */
class RouterTest extends TestCase {
	public function testGenerateConsecutively(): void {
		/** @var LoggerInterface $logger */
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('info')
			->willReturnCallback(
				function (string $message, array $data) {
					$this->fail('Unexpected info log: '.(string)($data['exception'] ?? $message));
				}
			);
		$router = new Router(
			$logger,
			$this->createMock(IRequest::class),
			$this->createMock(IConfig::class),
			$this->createMock(IEventLogger::class),
			$this->createMock(ContainerInterface::class),
		);

		$this->assertEquals('/index.php/apps/files/', $router->generate('files.view.index'));

		// the OCS route is the prefixed one for the AppFramework - see /ocs/v1.php for routing details
		$this->assertEquals('/index.php/ocsapp/apps/dav/api/v1/direct', $router->generate('ocs.dav.direct.getUrl'));

		// test caching
		$this->assertEquals('/index.php/apps/files/', $router->generate('files.view.index'));
	}
}
