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

use OC\Core\Command\Integrity\SignApp;
use OC\IntegrityCheck\Checker;
use OC\IntegrityCheck\Helpers\FileAccessHelper;
use OCP\IURLGenerator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Test\TestCase;

class SignAppTest extends TestCase {
	/** @var Checker */
	private $checker;
	/** @var SignApp */
	private $signApp;
	/** @var FileAccessHelper */
	private $fileAccessHelper;
	/** @var IURLGenerator */
	private $urlGenerator;

	public function setUp() {
		parent::setUp();
		$this->checker = $this->createMock(Checker::class);
		$this->fileAccessHelper = $this->createMock(FileAccessHelper::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->signApp = new SignApp(
			$this->checker,
			$this->fileAccessHelper,
			$this->urlGenerator
		);
	}

	public function testExecuteWithMissingPath() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('path')
			->will($this->returnValue(null));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue('PrivateKey'));
		$inputInterface
			->expects($this->at(2))
			->method('getOption')
			->with('certificate')
			->will($this->returnValue('Certificate'));

		$outputInterface
			->expects($this->at(0))
			->method('writeln')
			->with('This command requires the --path, --privateKey and --certificate.');

		$this->invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteWithMissingPrivateKey() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('path')
			->will($this->returnValue('AppId'));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue(null));
		$inputInterface
			->expects($this->at(2))
			->method('getOption')
			->with('certificate')
			->will($this->returnValue('Certificate'));

		$outputInterface
				->expects($this->at(0))
				->method('writeln')
				->with('This command requires the --path, --privateKey and --certificate.');

		$this->invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteWithMissingCertificate() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('path')
			->will($this->returnValue('AppId'));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue('privateKey'));
		$inputInterface
			->expects($this->at(2))
			->method('getOption')
			->with('certificate')
			->will($this->returnValue(null));

		$outputInterface
			->expects($this->at(0))
			->method('writeln')
			->with('This command requires the --path, --privateKey and --certificate.');

		$this->invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteWithNotExistingPrivateKey() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('path')
			->will($this->returnValue('AppId'));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue('privateKey'));
		$inputInterface
			->expects($this->at(2))
			->method('getOption')
			->with('certificate')
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

		$this->invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecuteWithNotExistingCertificate() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('path')
			->will($this->returnValue('AppId'));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue('privateKey'));
		$inputInterface
			->expects($this->at(2))
			->method('getOption')
			->with('certificate')
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

		$this->invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]);
	}

	public function testExecute() {
		$inputInterface = $this->createMock(InputInterface::class);
		$outputInterface = $this->createMock(OutputInterface::class);

		$inputInterface
			->expects($this->at(0))
			->method('getOption')
			->with('path')
			->will($this->returnValue('AppId'));
		$inputInterface
			->expects($this->at(1))
			->method('getOption')
			->with('privateKey')
			->will($this->returnValue('privateKey'));
		$inputInterface
			->expects($this->at(2))
			->method('getOption')
			->with('certificate')
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
			->method('writeAppSignature');

		$outputInterface
			->expects($this->at(0))
			->method('writeln')
			->with('Successfully signed "AppId"');

		$this->invokePrivate($this->signApp, 'execute', [$inputInterface, $outputInterface]);
	}
}
