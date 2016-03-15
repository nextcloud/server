<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Repair;

use OC\Repair\SharePropagation;

class RepairSharePropagation extends \Test\TestCase {
	public function keyProvider() {
		return [
			[['1', '2'], ['1', '2']],
			[['1', '2', 'foo'], ['1', '2']],
			[['foo'], []],
		];
	}

	/**
	 * @dataProvider keyProvider
	 * @param array $startKeys
	 * @param array $expectedRemovedKeys
	 */
	public function testRemovePropagationEntries(array $startKeys, array $expectedRemovedKeys) {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCP\IConfig $config */
		$config = $this->getMock('\OCP\IConfig');
		$config->expects($this->once())
			->method('getAppKeys')
			->with('files_sharing')
			->will($this->returnValue($startKeys));

		$removedKeys = [];

		$config->expects($this->any())
			->method('deleteAppValue')
			->will($this->returnCallback(function ($app, $key) use (&$removedKeys) {
				$removedKeys[] = $key;
			}));

		$step = new SharePropagation($config);
		$step->run();

		sort($expectedRemovedKeys);
		sort($removedKeys);

		$this->assertEquals($expectedRemovedKeys, $removedKeys);
	}
}
