<?php
/**
 * ownCloud
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\NavigationManager;

class NavigationManagerTest extends TestCase {
	/** @var \OC\NavigationManager */
	protected $navigationManager;

	protected function setUp() {
		parent::setUp();

		$this->navigationManager = new NavigationManager();
	}

	public function addArrayData() {
		return [
			[
				[
					'id'	=> 'entry id',
					'name'	=> 'link text',
					'order'	=> 1,
					'icon'	=> 'optional',
					'href'	=> 'url',
				],
				[
					'id'		=> 'entry id',
					'name'		=> 'link text',
					'order'		=> 1,
					'icon'		=> 'optional',
					'href'		=> 'url',
					'active'	=> false,
				],
			],
			[
				[
					'id'	=> 'entry id',
					'name'	=> 'link text',
					'order'	=> 1,
					//'icon'	=> 'optional',
					'href'	=> 'url',
					'active'	=> true,
				],
				[
					'id'		=> 'entry id',
					'name'		=> 'link text',
					'order'		=> 1,
					'icon'		=> '',
					'href'		=> 'url',
					'active'	=> false,
				],
			],
		];
	}

	/**
	 * @dataProvider addArrayData
	 *
	 * @param array $entry
	 * @param array $expectedEntry
	 */
	public function testAddArray(array $entry, array $expectedEntry) {
		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists');
		$this->navigationManager->add($entry);

		$navigationEntries = $this->navigationManager->getAll();
		$this->assertEquals(1, sizeof($navigationEntries), 'Expected that 1 navigation entry exists');
		$this->assertEquals($expectedEntry, $navigationEntries[0]);

		$this->navigationManager->clear();
		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists after clear()');
	}

	/**
	 * @dataProvider addArrayData
	 *
	 * @param array $entry
	 * @param array $expectedEntry
	 */
	public function testAddClosure(array $entry, array $expectedEntry) {
		global $testAddClosureNumberOfCalls;
		$testAddClosureNumberOfCalls = 0;

		$this->navigationManager->add(function () use ($entry) {
			global $testAddClosureNumberOfCalls;
			$testAddClosureNumberOfCalls++;

			return $entry;
		});

		$this->assertEquals(0, $testAddClosureNumberOfCalls, 'Expected that the closure is not called by add()');

		$navigationEntries = $this->navigationManager->getAll();
		$this->assertEquals(1, $testAddClosureNumberOfCalls, 'Expected that the closure is called by getAll()');
		$this->assertEquals(1, sizeof($navigationEntries), 'Expected that 1 navigation entry exists');
		$this->assertEquals($expectedEntry, $navigationEntries[0]);

		$navigationEntries = $this->navigationManager->getAll();
		$this->assertEquals(1, $testAddClosureNumberOfCalls, 'Expected that the closure is only called once for getAll()');
		$this->assertEquals(1, sizeof($navigationEntries), 'Expected that 1 navigation entry exists');
		$this->assertEquals($expectedEntry, $navigationEntries[0]);

		$this->navigationManager->clear();
		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists after clear()');
	}

	public function testAddArrayClearGetAll() {
		$entry = [
			'id'	=> 'entry id',
			'name'	=> 'link text',
			'order'	=> 1,
			'icon'	=> 'optional',
			'href'	=> 'url',
		];

		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists');
		$this->navigationManager->add($entry);
		$this->navigationManager->clear();
		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists after clear()');
	}

	public function testAddClosureClearGetAll() {
		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists');

		$entry = [
			'id'	=> 'entry id',
			'name'	=> 'link text',
			'order'	=> 1,
			'icon'	=> 'optional',
			'href'	=> 'url',
		];

		global $testAddClosureNumberOfCalls;
		$testAddClosureNumberOfCalls = 0;

		$this->navigationManager->add(function () use ($entry) {
			global $testAddClosureNumberOfCalls;
			$testAddClosureNumberOfCalls++;

			return $entry;
		});

		$this->assertEquals(0, $testAddClosureNumberOfCalls, 'Expected that the closure is not called by add()');
		$this->navigationManager->clear();
		$this->assertEquals(0, $testAddClosureNumberOfCalls, 'Expected that the closure is not called by clear()');
		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists after clear()');
		$this->assertEquals(0, $testAddClosureNumberOfCalls, 'Expected that the closure is not called by getAll()');
	}
}
