<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Tests\Core\Command\Maintenance;

use OC\Core\Command\Maintenance\DataFingerprint;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use Test\TestCase;

class DataFingerprintTest extends TestCase {
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $consoleInput;
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $consoleOutput;
	/** @var ITimeFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $timeFactory;

	/** @var \Symfony\Component\Console\Command\Command */
	protected $command;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->getMock('OCP\IConfig');
		$this->timeFactory = $this->getMock('OCP\AppFramework\Utility\ITimeFactory');
		$this->consoleInput = $this->getMock('Symfony\Component\Console\Input\InputInterface');
		$this->consoleOutput = $this->getMock('Symfony\Component\Console\Output\OutputInterface');

		/** @var \OCP\IConfig $config */
		$this->command = new DataFingerprint($this->config, $this->timeFactory);
	}

	public function testSetFingerPrint() {
		$this->timeFactory->expects($this->once())
			->method('getTime')
			->willReturn(42);
		$this->config->expects($this->once())
			->method('setSystemValue')
			->with('data-fingerprint', md5(42));

		self::invokePrivate($this->command, 'execute', [$this->consoleInput, $this->consoleOutput]);
	}
}
