<?php

namespace Test\Template;

use OC\SystemConfig;
use OC\Template\JSCombiner;
use OC\Template\SCSSCacher;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICache;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IURLGenerator;

class JSCombinerTest extends \Test\TestCase {
	/** @var IAppData|\PHPUnit_Framework_MockObject_MockObject */
	protected $appData;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	protected $urlGenerator;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var JSCombiner */
	protected $jsCombiner;
	/** @var ICache|\PHPUnit_Framework_MockObject_MockObject */
	protected $depsCache;

	protected function setUp() {
		parent::setUp();

		$this->appData = $this->createMock(IAppData::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(SystemConfig::class);
		$this->depsCache = $this->createMock(ICache::class);
		$this->jsCombiner = new JSCombiner(
			$this->appData,
			$this->urlGenerator,
			$this->depsCache,
			$this->config);
	}

	public function testProcessUncachedFileNoAppDataFolder() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())->method('getFolder')->with('awesomeapp')->willThrowException(new NotFoundException());
		$this->appData->expects($this->once())->method('newFolder')->with('awesomeapp')->willReturn($folder);
		$file = $this->createMock(ISimpleFile::class);

		$fileDeps = $this->createMock(ISimpleFile::class);

		$folder->method('getFile')
			->will($this->returnCallback(function($path) use ($file) {
				if ($path === 'combine.js') {
					return $file;
				} else if ($path === 'combine.js.deps') {
					throw new NotFoundException();
				} else {
					$this->fail();
				}
			}));
		$folder->expects($this->once())
			->method('newFile')
			->with('combine.js.deps')
			->willReturn($fileDeps);

		$actual = $this->jsCombiner->process(__DIR__, '/data/combine.json', 'awesomeapp');
		$this->assertTrue($actual);
	}

	public function testProcessUncachedFile() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())->method('getFolder')->with('awesomeapp')->willReturn($folder);
		$file = $this->createMock(ISimpleFile::class);

		$fileDeps = $this->createMock(ISimpleFile::class);

		$folder->method('getFile')
			->will($this->returnCallback(function($path) use ($file) {
				if ($path === 'combine.js') {
					return $file;
				} else if ($path === 'combine.js.deps') {
					throw new NotFoundException();
				} else {
					$this->fail();
				}
			}));
		$folder->expects($this->once())
			->method('newFile')
			->with('combine.js.deps')
			->willReturn($fileDeps);

		$actual = $this->jsCombiner->process(__DIR__, '/data/combine.json', 'awesomeapp');
		$this->assertTrue($actual);
	}

	public function testProcessCachedFile() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())->method('getFolder')->with('awesomeapp')->willReturn($folder);
		$file = $this->createMock(ISimpleFile::class);

		$fileDeps = $this->createMock(ISimpleFile::class);

		$fileDeps->expects($this->once())->method('getContent')->willReturn('{}');

		$folder->method('getFile')
			->will($this->returnCallback(function($path) use ($file, $fileDeps) {
				if ($path === 'combine.js') {
					return $file;
				} else if ($path === 'combine.js.deps') {
					return $fileDeps;
				} else {
					$this->fail();
				}
			}));

		$actual = $this->jsCombiner->process(__DIR__, '/data/combine.json', 'awesomeapp');
		$this->assertTrue($actual);
	}

	public function testProcessCachedFileMemcache() {
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())
			->method('getFolder')
			->with('awesomeapp')
			->willReturn($folder);
		$folder->method('getName')
			->willReturn('awesomeapp');

		$file = $this->createMock(ISimpleFile::class);

		$this->depsCache->method('get')
			->with('awesomeapp-combine.js.deps')
			->willReturn('{}');

		$folder->method('getFile')
			->will($this->returnCallback(function($path) use ($file) {
				if ($path === 'combine.js') {
					return $file;
				} else if ($path === 'combine.js.deps') {
					$this->fail();
				} else {
					$this->fail();
				}
			}));

		$actual = $this->jsCombiner->process(__DIR__, '/data/combine.json', 'awesomeapp');
		$this->assertTrue($actual);
	}

	public function testIsCachedNoDepsFile() {
		$fileName = "combine.json";
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);

		$folder->method('getFile')
			->will($this->returnCallback(function($path) use ($file) {
				if ($path === 'combine.js') {
					return $file;
				} else if ($path === 'combine.js.deps') {
					throw new NotFoundException();
				} else {
					$this->fail();
				}
			}));

		$actual = self::invokePrivate($this->jsCombiner, 'isCached', [$fileName, $folder]);
		$this->assertFalse($actual);
	}
	public function testCacheNoFile() {
		$fileName = "combine.js";

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);

		$path = __DIR__ . '/data/';

		$folder->expects($this->at(0))->method('getFile')->with($fileName)->willThrowException(new NotFoundException());
		$folder->expects($this->at(1))->method('newFile')->with($fileName)->willReturn($file);
		$folder->expects($this->at(2))->method('getFile')->with($fileName . '.deps')->willThrowException(new NotFoundException());
		$folder->expects($this->at(3))->method('newFile')->with($fileName . '.deps')->willReturn($depsFile);

		$file->expects($this->once())->method('putContent');
		$depsFile->expects($this->once())->method('putContent');

		$actual = self::invokePrivate($this->jsCombiner, 'cache', [$path, 'combine.json', $folder]);
		$this->assertTrue($actual);
	}

	public function testCache() {
		$fileName = "combine.js";

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);

		$path = __DIR__ . '/data/';

		$folder->expects($this->at(0))->method('getFile')->with($fileName)->willReturn($file);
		$folder->expects($this->at(1))->method('getFile')->with($fileName . '.deps')->willReturn($depsFile);

		$file->expects($this->once())->method('putContent');
		$depsFile->expects($this->once())->method('putContent');

		$actual = self::invokePrivate($this->jsCombiner, 'cache', [$path, 'combine.json', $folder]);
		$this->assertTrue($actual);
	}

	public function testCacheSuccess() {
		$fileName = 'combine.js';

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);

		$path = __DIR__ . '/data/';

		$folder->expects($this->at(0))->method('getFile')->with($fileName)->willReturn($file);
		$folder->expects($this->at(1))->method('getFile')->with($fileName . '.deps')->willReturn($depsFile);

		$file->expects($this->at(0))
			->method('putContent')
			->with('var a = \'hello\';


var b = \'world\';


');
		$depsFile->expects($this->at(0))->method('putContent')->with($this->callback(
			function ($content) {
				$deps = json_decode($content, true);
				return array_key_exists(__DIR__ . '/data//1.js', $deps)
					&& array_key_exists(__DIR__ . '/data//2.js', $deps);
			}));

		$actual = self::invokePrivate($this->jsCombiner, 'cache', [$path, 'combine.json', $folder]);
		$this->assertTrue($actual);
	}

	public function dataGetCachedSCSS() {
		return [
			['awesomeapp', 'core/js/foo.json', '/js/core/foo.js'],
			['files', 'apps/files/js/foo.json', '/js/files/foo.js']
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
			->with('core.Js.getJs', [
				'fileName' => 'foo.js',
				'appName' => $appName
			])
			->willReturn(\OC::$WEBROOT . $result);

		$actual = $this->jsCombiner->getCachedJS($appName, $fileName);
		$this->assertEquals(substr($result, 1), $actual);
	}


}
