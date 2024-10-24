<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests;

use OCA\Files_External\Lib\MissingDependency;

class LegacyDependencyCheckPolyfillTest extends \Test\TestCase {

	/**
	 * @return MissingDependency[]
	 */
	public static function checkDependencies() {
		return [
			(new MissingDependency('dependency'))->setMessage('missing dependency'),
			(new MissingDependency('program'))->setMessage('cannot find program'),
		];
	}

	public function testCheckDependencies(): void {
		$trait = $this->getMockForTrait('\OCA\Files_External\Lib\LegacyDependencyCheckPolyfill');
		$trait->expects($this->once())
			->method('getStorageClass')
			->willReturn('\OCA\Files_External\Tests\LegacyDependencyCheckPolyfillTest');

		$dependencies = $trait->checkDependencies();
		$this->assertCount(2, $dependencies);
		$this->assertEquals('dependency', $dependencies[0]->getDependency());
		$this->assertEquals('missing dependency', $dependencies[0]->getMessage());
		$this->assertEquals('program', $dependencies[1]->getDependency());
		$this->assertEquals('cannot find program', $dependencies[1]->getMessage());
	}
}
