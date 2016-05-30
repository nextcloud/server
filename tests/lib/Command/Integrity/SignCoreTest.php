<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
namespace Test\Command\Integrity;

use OC\Core\Command\Integrity\SignCore;
use OC\IntegrityCheck\Checker;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use Test\TestCase;

class SignCoreTest extends TestCase {
	/** @var Checker */
	private $checker;
	/** @var SignCore */
	private $signCore;
	/** @var FileAccessHelper */
	private $fileAccessHelper;

	public function setUp() {
		parent::setUp();
		$this->checker = $this->getMockBuilder('\OC\IntegrityCheck\Checker')
			->disableOriginalConstructor()->getMock();
		$this->fileAccessHelper = $this->getMockBuilder('\OC\IntegrityCheck\Helpers\FileAccessHelper')
			->disableOriginalConstructor()->getMock();
		$this->signCore = new SignCore(
			$this->checker,
			$this->fileAccessHelper
		);
	}

	public function testExecuteWithMissingPrivateKey() {
		$inputInterface = $this->getMock('\Symfony\Component\Console\Input\InputInterface');
		$outputInterface = $this->getMock('\Symfony\Component\Console\Output\OutputInterface');

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue(null));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('certificate')
			->will($this->returnValue('Certificate'));

		$outputInterface
			->expects($this->at(0))
			->method('writeln')
			->with('--privateKey, --certificate and --path are required.');

		$this->invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteWithMissingCertificate() {
		$inputInterface = $this->getMock('\Symfony\Component\Console\Input\InputInterface');
		$outputInterface = $this->getMock('\Symfony\Component\Console\Output\OutputInterface');

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue('privateKey'));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('certificate')
			->will($this->returnValue(null));

		$outputInterface
			->expects($this->at(0))
			->method('writeln')
			->with('--privateKey, --certificate and --path are required.');

		$this->invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteWithNotExistingPrivateKey() {
		$inputInterface = $this->getMock('\Symfony\Component\Console\Input\InputInterface');
		$outputInterface = $this->getMock('\Symfony\Component\Console\Output\OutputInterface');

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue('privateKey'));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('certificate')
			->will($this->returnValue('certificate'));
		$inputInterface
				->expects($this->at(2))
				->method('getOption')
				->with('path')
				->will($this->returnValue('certificate'));

		$this->fileAccessHelper
			->expects($this->at(0))
			->method('file_get_contents')
			->with('privateKey')
			->will($this->returnValue(false));

		$outputInterface
			->expects($this->at(0))
			->method('writeln')
			->with('Private key "privateKey" does not exists.');

		$this->invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteWithNotExistingCertificate() {
		$inputInterface = $this->getMock('\Symfony\Component\Console\Input\InputInterface');
		$outputInterface = $this->getMock('\Symfony\Component\Console\Output\OutputInterface');

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue('privateKey'));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('certificate')
			->will($this->returnValue('certificate'));
		$inputInterface
				->expects($this->at(2))
				->method('getOption')
				->with('path')
				->will($this->returnValue('certificate'));

		$this->fileAccessHelper
			->expects($this->at(0))
			->method('file_get_contents')
			->with('privateKey')
			->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/core.key'));
		$this->fileAccessHelper
			->expects($this->at(1))
			->method('file_get_contents')
			->with('certificate')
			->will($this->returnValue(false));

		$outputInterface
			->expects($this->at(0))
			->method('writeln')
			->with('Certificate "certificate" does not exists.');

		$this->invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecute() {
		$inputInterface = $this->getMock('\Symfony\Component\Console\Input\InputInterface');
		$outputInterface = $this->getMock('\Symfony\Component\Console\Output\OutputInterface');

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue('privateKey'));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('certificate')
			->will($this->returnValue('certificate'));
		$inputInterface
				->expects($this->at(2))
				->method('getOption')
				->with('path')
				->will($this->returnValue('certificate'));

		$this->fileAccessHelper
			->expects($this->at(0))
			->method('file_get_contents')
			->with('privateKey')
			->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/core.key'));
		$this->fileAccessHelper
			->expects($this->at(1))
			->method('file_get_contents')
			->with('certificate')
			->will($this->returnValue(\OC::$SERVERROOT . '/tests/data/integritycheck/core.crt'));

		$this->checker
			->expects($this->once())
			->method('writeCoreSignature');

		$outputInterface
			->expects($this->at(0))
			->method('writeln')
			->with('Successfully signed "core"');

		$this->invokePrivate($this->signCore, 'execute', [$inputInterface, $outputInterface]);
	}
}
