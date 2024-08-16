<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\AppScriptDependency;
use OC\AppScriptSort;
use Psr\Log\LoggerInterface;

/**
 * Class AppScriptSortTest
 *
 * @package Test
 * @group DB
 */
class AppScriptSortTest extends \Test\TestCase {
	private $logger;

	protected function setUp(): void {
		$this->logger = $this->getMockBuilder(LoggerInterface::class)
			->disableOriginalConstructor()
			->getMock();

		parent::setUp();
	}

	public function testSort(): void {
		$scripts = [
			'first' => ['myFirstJSFile'],
			'core' => [
				'core/js/myFancyJSFile1',
				'core/js/myFancyJSFile4',
				'core/js/myFancyJSFile5',
				'core/js/myFancyJSFile1',
			],
			'files' => ['files/js/myFancyJSFile2'],
			'myApp5' => ['myApp5/js/myApp5JSFile'],
			'myApp' => ['myApp/js/myFancyJSFile3'],
			'myApp4' => ['myApp4/js/myApp4JSFile'],
			'myApp3' => ['myApp3/js/myApp3JSFile'],
			'myApp2' => ['myApp2/js/myApp2JSFile'],
		];
		$scriptDeps = [
			'first' => new AppScriptDependency('first', ['core']),
			'core' => new AppScriptDependency('core', ['core']),
			'files' => new AppScriptDependency('files', ['core']),
			'myApp5' => new AppScriptDependency('myApp5', ['myApp2']),
			'myApp' => new AppScriptDependency('myApp', ['core']),
			'myApp4' => new AppScriptDependency('myApp4', ['myApp3']),
			'myApp3' => new AppScriptDependency('myApp3', ['myApp2']),
			'myApp2' => new AppScriptDependency('myApp2', ['myApp']),
		];

		// No circular dependency is detected and logged as an error
		$this->logger->expects(self::never())->method('error');

		$scriptSort = new AppScriptSort($this->logger);
		$sortedScripts = $scriptSort->sort($scripts, $scriptDeps);

		$sortedScriptKeys = array_keys($sortedScripts);

		// Core should appear first
		$this->assertEquals(
			0,
			array_search('core', $sortedScriptKeys, true)
		);

		// Dependencies should appear before their children
		$this->assertLessThan(
			array_search('files', $sortedScriptKeys, true),
			array_search('core', $sortedScriptKeys, true)
		);
		$this->assertLessThan(
			array_search('myApp2', $sortedScriptKeys, true),
			array_search('myApp', $sortedScriptKeys, true)
		);
		$this->assertLessThan(
			array_search('myApp3', $sortedScriptKeys, true),
			array_search('myApp2', $sortedScriptKeys, true)
		);
		$this->assertLessThan(
			array_search('myApp4', $sortedScriptKeys, true),
			array_search('myApp3', $sortedScriptKeys, true)
		);
		$this->assertLessThan(
			array_search('myApp5', $sortedScriptKeys, true),
			array_search('myApp2', $sortedScriptKeys, true)
		);

		// All apps still there
		foreach ($scripts as $app => $_) {
			$this->assertContains($app, $sortedScriptKeys);
		}
	}

	public function testSortCircularDependency(): void {
		$scripts = [
			'circular' => ['circular/js/file1'],
			'dependency' => ['dependency/js/file2'],
		];
		$scriptDeps = [
			'circular' => new AppScriptDependency('circular', ['dependency']),
			'dependency' => new AppScriptDependency('dependency', ['circular']),
		];

		// A circular dependency is detected and logged as an error
		$this->logger->expects(self::once())->method('error');

		$scriptSort = new AppScriptSort($this->logger);
		$sortedScripts = $scriptSort->sort($scripts, $scriptDeps);

		$sortedScriptKeys = array_keys($sortedScripts);

		// All apps still there
		foreach ($scripts as $app => $_) {
			$this->assertContains($app, $sortedScriptKeys);
		}
	}
}
