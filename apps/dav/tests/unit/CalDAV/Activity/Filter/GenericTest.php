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
namespace OCA\DAV\Tests\unit\CalDAV\Activity\Filter;

use OC;
use OCA\DAV\CalDAV\Activity\Filter\Calendar;
use OCA\DAV\CalDAV\Activity\Filter\Todo;
use OCP\Activity\IFilter;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Test\TestCase;

/**
 * @group DB
 */
class GenericTest extends TestCase {
	public function dataFilters(): array {
		return [
			[Calendar::class],
			[Todo::class],
		];
	}

	/**
	 * @dataProvider dataFilters
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testImplementsInterface(string $filterClass) {
		$filter = OC::$server->get($filterClass);
		$this->assertInstanceOf(IFilter::class, $filter);
	}

	/**
	 * @dataProvider dataFilters
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testGetIdentifier(string $filterClass) {
		/** @var IFilter $filter */
		$filter = OC::$server->get($filterClass);
		$this->assertIsString($filter->getIdentifier());
	}

	/**
	 * @dataProvider dataFilters
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testGetName(string $filterClass) {
		/** @var IFilter $filter */
		$filter = OC::$server->get($filterClass);
		$this->assertIsString($filter->getName());
	}

	/**
	 * @dataProvider dataFilters
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testGetPriority(string $filterClass) {
		/** @var IFilter $filter */
		$filter = OC::$server->get($filterClass);
		$priority = $filter->getPriority();
		$this->assertIsInt($filter->getPriority());
		$this->assertGreaterThanOrEqual(0, $priority);
		$this->assertLessThanOrEqual(100, $priority);
	}

	/**
	 * @dataProvider dataFilters
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testGetIcon(string $filterClass) {
		/** @var IFilter $filter */
		$filter = OC::$server->get($filterClass);
		$this->assertIsString($filter->getIcon());
		$this->assertStringStartsWith('http', $filter->getIcon());
	}

	/**
	 * @dataProvider dataFilters
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testFilterTypes(string $filterClass) {
		/** @var IFilter $filter */
		$filter = OC::$server->get($filterClass);
		$this->assertIsArray($filter->filterTypes([]));
	}

	/**
	 * @dataProvider dataFilters
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	public function testAllowedApps(string $filterClass) {
		/** @var IFilter $filter */
		$filter = OC::$server->get($filterClass);
		$this->assertIsArray($filter->allowedApps());
	}
}
