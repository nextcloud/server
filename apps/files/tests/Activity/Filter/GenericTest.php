<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Tests\Activity\Filter;

use OCA\Files\Activity\Filter\Favorites;
use OCA\Files\Activity\Filter\FileChanges;
use OCP\Activity\IFilter;
use OCP\Server;
use Test\TestCase;

/**
 * Class GenericTest
 *
 * @package OCA\Files\Tests\Activity\Filter
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class GenericTest extends TestCase {
	public static function dataFilters(): array {
		return [
			[Favorites::class],
			[FileChanges::class],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataFilters')]
	public function testImplementsInterface(string $filterClass): void {
		$filter = Server::get($filterClass);
		$this->assertInstanceOf(IFilter::class, $filter);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataFilters')]
	public function testGetIdentifier(string $filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$this->assertIsString($filter->getIdentifier());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataFilters')]
	public function testGetName(string $filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$this->assertIsString($filter->getName());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataFilters')]
	public function testGetPriority(string $filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$priority = $filter->getPriority();
		$this->assertIsInt($filter->getPriority());
		$this->assertGreaterThanOrEqual(0, $priority);
		$this->assertLessThanOrEqual(100, $priority);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataFilters')]
	public function testGetIcon(string $filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$this->assertIsString($filter->getIcon());
		$this->assertStringStartsWith('http', $filter->getIcon());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataFilters')]
	public function testFilterTypes(string $filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$this->assertIsArray($filter->filterTypes([]));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataFilters')]
	public function testAllowedApps(string $filterClass): void {
		/** @var IFilter $filter */
		$filter = Server::get($filterClass);
		$this->assertIsArray($filter->allowedApps());
	}
}
