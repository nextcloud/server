<?php
/**
 * @copyright Copyright (c) 2018 Arne Hamann <kontakt+github@arne.email>
 *
 * @author Arne Hamann <kontakt+github@arne.email>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Repair\NC14;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\ILogger;
use OCP\IConfig;
use OCP\IGpg;
use Test\TestCase;
use OC\Repair\NC14\CreateGpgServerKeys;


/**
 * Class CreateGpgServerKeysTest

 */
class CreateGpgServerKeysTest extends TestCase {
	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var ILogger | \PHPUnit_Framework_MockObject_MockObject*/
	private $logger;

	/** @var IGpg | \PHPUnit_Framework_MockObject_MockObject*/
	private $gpg;

	protected function setUp() {
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->gpg = $this->createMock(IGpg::class);
		$this->config = $this->createMock(IConfig::class);
	}

	/**
	 * @dataProvider dataRun
	 * @param $keys1
	 */
	public function testRun($keys1) {
		/** @var IOutput|\PHPUnit_Framework_MockObject_MockObject $outputMock */
		$outputMock = $this->createMock(IOutput::class);
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->willReturn('');
		$this->gpg
			->expects($this->once())
			->method('generateKey')
			->willReturn('abcdefghijklmnopqrs');
		$this->gpg->expects($this->once())
			->method('keyinfo')
			->will($this->onConsecutiveCalls($keys1));
		if ($keys1 === FALSE || $keys1 === []) {
			$this->logger->expects($this->once())
				->method('error');
		}
		// run repair step
		$repair = new CreateGpgServerKeys($this->config, $this->logger, $this->gpg);
		$repair->run($outputMock);
	}

	/**
	 * @dataProvider dataSecondRun
	 * @param $fingerprint
	 * @param $fingerprintReturn
	 * @param $keys1
	 * @param $keys2
	 */
	public function testSecondRun( $keys1, $keys2) {
		/** @var IOutput|\PHPUnit_Framework_MockObject_MockObject $outputMock */
		$outputMock = $this->createMock(IOutput::class);
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->willReturn('abcdefghijklnop');
		if ($keys1 === FALSE || $keys1 === []) {
			$this->gpg
				->expects($this->once())
				->method('generateKey')
				->willReturn('abcdefghijklmnop');
		} else {
			$this->gpg
				->expects($this->never())
				->method('generateKey');
		}
		$this->gpg->expects($this->any())
			->method('keyinfo')
			->will($this->onConsecutiveCalls($keys1,$keys2));

		if ($keys2 === FALSE || $keys2 === []) {
			$this->logger->expects($this->once())
				->method('error');
		}

		// run repair step
		$repair = new CreateGpgServerKeys($this->config, $this->logger, $this->gpg);
		$repair->run($outputMock);
	}

	public function dataRun(){
		return [
			[[]],
			[[['keyarray']]],
			[FALSE]
		];
	}

	public function dataSecondRun(){
		$keyinfoPossibleReturn = [[], FALSE,[['keyarray']]];
		$return = [];
		foreach($keyinfoPossibleReturn as $p1) {
			foreach ($keyinfoPossibleReturn as $p2) {
				$return[] = [$p1,$p2];
			}
		}
		return $return;
	}
}

