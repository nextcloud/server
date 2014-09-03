<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

use \OC\Security\SecureRandom;

class SecureRandomTest extends \PHPUnit_Framework_TestCase {

	public function stringGenerationProvider() {
		return array(
			array(0, 0),
			array(1, 1),
			array(128, 128),
			array(256, 256),
			array(1024, 1024),
			array(2048, 2048),
			array(64000, 64000),
		);
	}

	/** @var SecureRandom */
	protected $rng;

	protected function setUp() {
		$this->rng = new \OC\Security\SecureRandom();
	}

	/**
	 * @dataProvider stringGenerationProvider
	 */
	function testGetLowStrengthGeneratorLength($length, $expectedLength) {
		$generator = $this->rng->getLowStrengthGenerator();

		$this->assertEquals($expectedLength, strlen($generator->generate($length)));
	}

	/**
	 * @dataProvider stringGenerationProvider
	 */
	function testMediumLowStrengthGeneratorLength($length, $expectedLength) {
		$generator = $this->rng->getMediumStrengthGenerator();

		$this->assertEquals($expectedLength, strlen($generator->generate($length)));
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Generator is not initialized
	 */
	function testUninitializedGenerate() {
		$this->rng->generate(30);
	}
}
