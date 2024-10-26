<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Tests\Activity\Filter;

use OCA\Files\Activity\Filter\Favorites;
use OCA\Files\Activity\Filter\FileChanges;
use OCP\Activity\IFilter;
use Test\TestCase;

/**
 * Class GenericTest
 *
 * @package OCA\Files\Tests\Activity\Filter
 * @group DB
 */
class GenericTest extends TestCase {
	public function dataFilters() {
		return [
			[Favorites::class],
			[FileChanges::class],
		];
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testImplementsInterface($filterClass): void {
		$filter = \OC::$server->query($filterClass);
		$this->assertInstanceOf(IFilter::class, $filter);
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testGetIdentifier($filterClass): void {
		/** @var IFilter $filter */
		$filter = \OC::$server->query($filterClass);
		$this->assertIsString($filter->getIdentifier());
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testGetName($filterClass): void {
		/** @var IFilter $filter */
		$filter = \OC::$server->query($filterClass);
		$this->assertIsString($filter->getName());
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testGetPriority($filterClass): void {
		/** @var IFilter $filter */
		$filter = \OC::$server->query($filterClass);
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
		$filter = \OC::$server->query($filterClass);
		$this->assertIsString($filter->getIcon());
		$this->assertStringStartsWith('http', $filter->getIcon());
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testFilterTypes($filterClass): void {
		/** @var IFilter $filter */
		$filter = \OC::$server->query($filterClass);
		$this->assertIsArray($filter->filterTypes([]));
	}

	/**
	 * @dataProvider dataFilters
	 * @param string $filterClass
	 */
	public function testAllowedApps($filterClass): void {
		/** @var IFilter $filter */
		$filter = \OC::$server->query($filterClass);
		$this->assertIsArray($filter->allowedApps());
	}
}
