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
		$this->scssCacher = new SCSSCacher(
			$this->logger,
			$this->appData,
			$this->urlGenerator,
			$this->config,
			\OC::$SERVERROOT
		);
	}

	public function testProcessUncachedFileNoAppDataFolder() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())->method('getFolder')->with('core')->willThrowException(new NotFoundException());
		$this->appData->expects($this->once())->method('newFolder')->with('core')->willReturn($folder);
		$file = $this->createMock(ISimpleFile::class);
		$file->expects($this->any())->method('getSize')->willReturn(1);
		$fileDeps = $this->createMock(ISimpleFile::class);
		$folder->expects($this->at(0))->method('getFile')->with('styles.css')->willReturn($file);
		$folder->expects($this->at(1))->method('getFile')->with('styles.css.deps')->willThrowException(new NotFoundException());
		$folder->expects($this->at(2))->method('getFile')->with('styles.css')->willReturn($file);
		$folder->expects($this->at(3))->method('getFile')->with('styles.css.deps')->willThrowException(new NotFoundException());
		$folder->expects($this->at(4))->method('newFile')->with('styles.css.deps')->willReturn($fileDeps);
		$actual = $this->scssCacher->process(\OC::$SERVERROOT, '/core/css/styles.scss', 'core');
		$this->assertTrue($actual);
	}

	public function testProcessUncachedFile() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())->method('getFolder')->with('core')->willReturn($folder);
		$file = $this->createMock(ISimpleFile::class);
		$file->expects($this->any())->method('getSize')->willReturn(1);
		$fileDeps = $this->createMock(ISimpleFile::class);
		$folder->expects($this->at(0))->method('getFile')->with('styles.css')->willReturn($file);
		$folder->expects($this->at(1))->method('getFile')->with('styles.css.deps')->willThrowException(new NotFoundException());
		$folder->expects($this->at(2))->method('getFile')->with('styles.css')->willReturn($file);
		$folder->expects($this->at(3))->method('getFile')->with('styles.css.deps')->willThrowException(new NotFoundException());
		$folder->expects($this->at(4))->method('newFile')->with('styles.css.deps')->willReturn($fileDeps);
		$actual = $this->scssCacher->process(\OC::$SERVERROOT, '/core/css/styles.scss', 'core');
		$this->assertTrue($actual);
	}

	public function testProcessCacheFile() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())->method('getFolder')->with('core')->willReturn($folder);
		$file = $this->createMock(ISimpleFile::class);
		$file->expects($this->any())->method('getSize')->willReturn(1);
		$fileDeps = $this->createMock(ISimpleFile::class);
		$fileDeps->expects($this->any())->method('getSize')->willReturn(1);
		$fileDeps->expects($this->once())->method('getContent')->willReturn('{}');
		$folder->expects($this->at(0))->method('getFile')->with('styles.css')->willReturn($file);
		$folder->expects($this->at(1))->method('getFile')->with('styles.css.deps')->willReturn($fileDeps);
		$actual = $this->scssCacher->process(\OC::$SERVERROOT, '/core/css/styles.scss', 'core');
		$this->assertTrue($actual);
	}

	public function testProcessCachedFile() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())->method('getFolder')->with('core')->willReturn($folder);
		$file = $this->createMock(ISimpleFile::class);
		$file->expects($this->once())->method('getSize')->willReturn(1);
		$fileDeps = $this->createMock(ISimpleFile::class);
		$fileDeps->expects($this->any())->method('getSize')->willReturn(1);
		$fileDeps->expects($this->once())->method('getContent')->willReturn('{}');
		$folder->expects($this->at(0))->method('getFile')->with('styles.css')->willReturn($file);
		$folder->expects($this->at(1))->method('getFile')->with('styles.css.deps')->willReturn($fileDeps);
		$actual = $this->scssCacher->process(\OC::$SERVERROOT, '/core/css/styles.scss', 'core');
		$this->assertTrue($actual);
	}

	public function testIsCachedNoFile() {
		$fileNameCSS = "styles.css";
		$fileNameSCSS = "styles.scss";
		$folder = $this->createMock(ISimpleFolder::class);
		$path = \OC::$SERVERROOT . '/core/css/';

		$folder->expects($this->at(0))->method('getFile')->with($fileNameCSS)->willThrowException(new NotFoundException());
		$actual = self::invokePrivate($this->scssCacher, 'isCached', [$fileNameCSS, $fileNameSCSS, $folder, $path]);
		$this->assertFalse($actual);
	}

	public function testIsCachedNoDepsFile() {
		$fileNameCSS = "styles.css";
		$fileNameSCSS = "styles.scss";
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$path = \OC::$SERVERROOT . '/core/css/';

		$file->expects($this->once())->method('getSize')->willReturn(1);
		$folder->expects($this->at(0))->method('getFile')->with($fileNameCSS)->willReturn($file);
		$folder->expects($this->at(1))->method('getFile')->with($fileNameCSS . '.deps')->willThrowException(new NotFoundException());

		$actual = self::invokePrivate($this->scssCacher, 'isCached', [$fileNameCSS, $fileNameSCSS, $folder, $path]);
		$this->assertFalse($actual);
	}
	public function testCacheNoFile() {
		$fileNameCSS = "styles.css";
		$fileNameSCSS = "styles.scss";
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);

		$webDir = "core/css";
		$path = \OC::$SERVERROOT . '/core/css/';

		$folder->expects($this->at(0))->method('getFile')->with($fileNameCSS)->willThrowException(new NotFoundException());
		$folder->expects($this->at(1))->method('newFile')->with($fileNameCSS)->willReturn($file);
		$folder->expects($this->at(2))->method('getFile')->with($fileNameCSS . '.deps')->willThrowException(new NotFoundException());
		$folder->expects($this->at(3))->method('newFile')->with($fileNameCSS . '.deps')->willReturn($depsFile);

		$file->expects($this->once())->method('putContent');
		$depsFile->expects($this->once())->method('putContent');

		$actual = self::invokePrivate($this->scssCacher, 'cache', [$path, $fileNameCSS, $fileNameSCSS, $folder, $webDir]);
		$this->assertTrue($actual);
	}

	public function testCache() {
		$fileNameCSS = "styles.css";
		$fileNameSCSS = "styles.scss";
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);

		$webDir = "core/css";
		$path = \OC::$SERVERROOT;

		$folder->expects($this->at(0))->method('getFile')->with($fileNameCSS)->willReturn($file);
		$folder->expects($this->at(1))->method('getFile')->with($fileNameCSS . '.deps')->willReturn($depsFile);

		$file->expects($this->once())->method('putContent');
		$depsFile->expects($this->once())->method('putContent');

		$actual = self::invokePrivate($this->scssCacher, 'cache', [$path, $fileNameCSS, $fileNameSCSS, $folder, $webDir]);
		$this->assertTrue($actual);
	}

	public function testCacheSuccess() {
		$fileNameCSS = "styles-success.css";
		$fileNameSCSS = "../../tests/data/scss/styles-success.scss";
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);

		$webDir = "tests/data/scss";
		$path = \OC::$SERVERROOT . $webDir;

		$folder->expects($this->at(0))->method('getFile')->with($fileNameCSS)->willReturn($file);
		$folder->expects($this->at(1))->method('getFile')->with($fileNameCSS . '.deps')->willReturn($depsFile);

		$file->expects($this->at(0))->method('putContent')->with($this->callback(
			function ($content){
				return 'body{background-color:#0082c9}' === $content;
			}));
		$depsFile->expects($this->at(0))->method('putContent')->with($this->callback(
			function ($content) {
				$deps = json_decode($content, true);
				return array_key_exists(\OC::$SERVERROOT . '/core/css/variables.scss', $deps)
					&& array_key_exists(\OC::$SERVERROOT . '/tests/data/scss/styles-success.scss', $deps);
			}));

		$actual = self::invokePrivate($this->scssCacher, 'cache', [$path, $fileNameCSS, $fileNameSCSS, $folder, $webDir]);
		$this->assertTrue($actual);
	}

	public function testCacheFailure() {
		$fileNameCSS = "styles-error.css";
		$fileNameSCSS = "../../tests/data/scss/styles-error.scss";
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);

		$webDir = "/tests/data/scss";
		$path = \OC::$SERVERROOT . $webDir;

		$folder->expects($this->at(0))->method('getFile')->with($fileNameCSS)->willReturn($file);
		$folder->expects($this->at(1))->method('getFile')->with($fileNameCSS . '.deps')->willReturn($depsFile);

		$actual = self::invokePrivate($this->scssCacher, 'cache', [$path, $fileNameCSS, $fileNameSCSS, $folder, $webDir]);
		$this->assertFalse($actual);
	}

	public function testRebaseUrls() {
		$webDir = 'apps/files/css';
		$css = '#id { background-image: url(\'../img/image.jpg\'); }';
		$actual = self::invokePrivate($this->scssCacher, 'rebaseUrls', [$css, $webDir]);
		$expected = '#id { background-image: url(\'../../../apps/files/css/../img/image.jpg\'); }';
		$this->assertEquals($expected, $actual);
	}

	public function testRebaseUrlsIgnoreFrontendController() {
		$this->config->expects($this->once())->method('getSystemValue')->with('htaccess.IgnoreFrontController', false)->willReturn(true);
		$webDir = 'apps/files/css';
		$css = '#id { background-image: url(\'../img/image.jpg\'); }';
		$actual = self::invokePrivate($this->scssCacher, 'rebaseUrls', [$css, $webDir]);
		$expected = '#id { background-image: url(\'../../apps/files/css/../img/image.jpg\'); }';
		$this->assertEquals($expected, $actual);
	}

	public function dataGetCachedSCSS() {
		return [
			['core', 'core/css/styles.scss', '/css/core/styles.css'],
			['files', 'apps/files/css/styles.scss', '/css/files/styles.css']
		];
	}

	/**
	 * @param $appName
	 * @param $fileName
	 * @param $result
	 * @dataProvider dataGetCachedSCSS
	 */
	public function testGetCachedSCSS($appName, $fileName, $result) {
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('core.Css.getCss', [
				'fileName' => 'styles.css',
				'appName' => $appName
			])
			->willReturn(\OC::$WEBROOT . $result);
		$actual = $this->scssCacher->getCachedSCSS($appName, $fileName);
		$this->assertEquals(substr($result, 1), $actual);
	}


}