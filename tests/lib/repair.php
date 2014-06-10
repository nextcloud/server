<?php
/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use OC\Hooks\BasicEmitter;

class TestRepairStep extends BasicEmitter implements \OC\RepairStep{
	private $warning;

	public function __construct($warning = false) {
		$this->warning = $warning;
	}

	public function getName() {
		return 'Test Name';
	}

	public function run() {
		if ($this->warning) {
			$this->emit('\OC\Repair', 'warning', array('Simulated warning'));
		}
		else {
			$this->emit('\OC\Repair', 'info', array('Simulated info'));
		}
	}
}

class Test_Repair extends PHPUnit_Framework_TestCase {
	public function testRunRepairStep() {
		$output = array();

		$repair = new \OC\Repair();
		$repair->addStep(new TestRepairStep(false));

		$repair->listen('\OC\Repair', 'warning', function ($description) use (&$output) {
			$output[] = 'warning: ' . $description;
		});
		$repair->listen('\OC\Repair', 'info', function ($description) use (&$output) {
			$output[] = 'info: ' . $description;
		});
		$repair->listen('\OC\Repair', 'step', function ($description) use (&$output) {
			$output[] = 'step: ' . $description;
		});

		$repair->run();

		$this->assertEquals(
			array(
				'step: Test Name',
				'info: Simulated info',
			),
			$output
		);
	}

	public function testRunRepairStepThatFail() {
		$output = array();

		$repair = new \OC\Repair();
		$repair->addStep(new TestRepairStep(true));

		$repair->listen('\OC\Repair', 'warning', function ($description) use (&$output) {
			$output[] = 'warning: ' . $description;
		});
		$repair->listen('\OC\Repair', 'info', function ($description) use (&$output) {
			$output[] = 'info: ' . $description;
		});
		$repair->listen('\OC\Repair', 'step', function ($description) use (&$output) {
			$output[] = 'step: ' . $description;
		});

		$repair->run();

		$this->assertEquals(
			array(
				'step: Test Name',
				'warning: Simulated warning',
			),
			$output
		);
	}

	public function testRunRepairStepsWithException() {
		$output = array();

		$mock = $this->getMock('TestRepairStep');
		$mock->expects($this->any())
			->method('run')
			->will($this->throwException(new Exception));
		$mock->expects($this->any())
			->method('getName')
			->will($this->returnValue('Exception Test'));

		$repair = new \OC\Repair();
		$repair->addStep($mock);
		$repair->addStep(new TestRepairStep(false));

		$repair->listen('\OC\Repair', 'warning', function ($description) use (&$output) {
			$output[] = 'warning: ' . $description;
		});
		$repair->listen('\OC\Repair', 'info', function ($description) use (&$output) {
			$output[] = 'info: ' . $description;
		});
		$repair->listen('\OC\Repair', 'step', function ($description) use (&$output) {
			$output[] = 'step: ' . $description;
		});

		$thrown = false;
		try {
			$repair->run();
		}
		catch (Exception $e) {
			$thrown = true;
		}

		$this->assertTrue($thrown);
		// jump out after exception
		$this->assertEquals(
			array(
				'step: Exception Test',
			),
			$output
		);
	}

	public function testRunRepairStepsContinueAfterWarning() {
		$output = array();

		$repair = new \OC\Repair();
		$repair->addStep(new TestRepairStep(true));
		$repair->addStep(new TestRepairStep(false));

		$repair->listen('\OC\Repair', 'warning', function ($description) use (&$output) {
			$output[] = 'warning: ' . $description;
		});
		$repair->listen('\OC\Repair', 'info', function ($description) use (&$output) {
			$output[] = 'info: ' . $description;
		});
		$repair->listen('\OC\Repair', 'step', function ($description) use (&$output) {
			$output[] = 'step: ' . $description;
		});

		$repair->run();

		$this->assertEquals(
			array(
				'step: Test Name',
				'warning: Simulated warning',
				'step: Test Name',
				'info: Simulated info',
			),
			$output
		);
	}
}
