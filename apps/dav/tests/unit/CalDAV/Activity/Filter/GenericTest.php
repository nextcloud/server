<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Activity\Filter;

use OCA\DAV\CalDAV\Activity\Filter\Calendar;
use OCA\DAV\CalDAV\Activity\Filter\Todo;
use OCP\Activity\IFilter;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class GenericTest extends TestCase {
	public function dataFilters() {
		return [
			[Calendar::class],
			[Todo::class],
		];
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testImplementsInterface($filterClass): void {
		$filter = Server::get($filterClass);
		$this->assertInstanceOf(IFilter::class, $filter);
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testGetIdentifier($filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$this->assertIsString($filter->getIdentifier());
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testGetName($filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$this->assertIsString($filter->getName());
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testGetPriority($filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$priority = $filter->getPriority();
		$this->assertIsInt($filter->getPriority());
		$this->assertGreaterThanOrEqual(0, $priority);
		$this->assertLessThanOrEqual(100, $priority);
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testGetIcon($filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$this->assertIsString($filter->getIcon());
		$this->assertStringStartsWith('http', $filter->getIcon());
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testFilterTypes($filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$this->assertIsArray($filter->filterTypes([]));
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testAllowedApps($filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$this->assertIsArray($filter->allowedApps());
	}
}
