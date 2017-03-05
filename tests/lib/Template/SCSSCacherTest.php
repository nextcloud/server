<?php
/**
 * @copyright Copyright (c) 2017 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Template;

use OC\Template\SCSSCacher;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IURLGenerator;

class SCSSCacherTest extends \Test\TestCase {
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	protected $logger;
	/** @var IAppData|\PHPUnit_Framework_MockObject_MockObject */
	protected $appData;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	protected $urlGenerator;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var \OC_Defaults|\PHPUnit_Framework_MockObject_MockObject */
	protected $defaults;
	/** @var SCSSCacher */
	protected $scssCacher;

	protected function setUp() {
		parent::setUp();
		$this->logger = $this->createMock(ILogger::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->defaults = $this->createMock(\OC_Defaults::class);
		$this->scssCacher = new SCSSCacher(
			$this->logger,
			$this->appData,
			$this->urlGenerator,
			$this->config,
			$this->defaults
		);
	}

	public function testProcess() {

	}

	public function testVariablesChangedNotFound() {
		$mtime = filemtime(\OC::$SERVERROOT . '/core/css/variables.scss');
		$file = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->expects($this->once())
			->method('getFile')
			->with('styles.scss')
			->willThrowException(new NotFoundException());
		$this->assertTrue($this->invokePrivate($this->scssCacher, 'variablesChanged', ['styles.scss', $folder]));
	}

	public function testVariablesChangedOlder() {
		$mtime = filemtime(\OC::$SERVERROOT . '/core/css/variables.scss');
		$file = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->expects($this->once())
			->method('getFile')
			->with('styles.scss')
			->willReturn($file);
		$file->expects($this->once())
			->method('getMTime')
			->willReturn($mtime-100);
		$this->assertTrue($this->invokePrivate($this->scssCacher, 'variablesChanged', ['styles.scss', $folder]));
	}

	public function testVariablesChangedNewer() {
		$mtime = filemtime(\OC::$SERVERROOT . '/core/css/variables.scss');
		$file = $this->createMock(ISimpleFile::class);
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->expects($this->once())
			->method('getFile')
			->with('styles.scss')
			->willReturn($file);
		$file->expects($this->once())
			->method('getMTime')
			->willReturn($mtime+100);
		$this->assertFalse($this->invokePrivate($this->scssCacher, 'variablesChanged', ['styles.scss', $folder]));
	}

}