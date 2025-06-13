<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Template;

use OC\SystemConfig;
use OC\Template\JSCombiner;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\ITempManager;
use OCP\IURLGenerator;
use OCP\Server;
use Psr\Log\LoggerInterface;

class JSCombinerTest extends \Test\TestCase {
	/** @var IAppData|\PHPUnit\Framework\MockObject\MockObject */
	protected $appData;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	protected $urlGenerator;
	/** @var SystemConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var ICache|\PHPUnit\Framework\MockObject\MockObject */
	protected $depsCache;
	/** @var JSCombiner */
	protected $jsCombiner;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;
	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $cacheFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->appData = $this->createMock(IAppData::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(SystemConfig::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->depsCache = $this->createMock(ICache::class);
		$this->cacheFactory->expects($this->atLeastOnce())
			->method('createDistributed')
			->willReturn($this->depsCache);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->jsCombiner = new JSCombiner(
			$this->appData,
			$this->urlGenerator,
			$this->cacheFactory,
			$this->config,
			$this->logger
		);
	}

	public function testProcessDebugMode(): void {
		$this->config
			->expects($this->once())
			->method('getValue')
			->with('debug')
			->willReturn(true);

		$actual = $this->jsCombiner->process(__DIR__, '/data/combine.json', 'awesomeapp');
		$this->assertFalse($actual);
	}

	public function testProcessNotInstalled(): void {
		$this->config
			->expects($this->exactly(2))
			->method('getValue')
			->willReturnMap([
				['debug', false],
				['installed', false]
			]);

		$actual = $this->jsCombiner->process(__DIR__, '/data/combine.json', 'awesomeapp');
		$this->assertFalse($actual);
	}

	public function testProcessUncachedFileNoAppDataFolder(): void {
		$this->config
			->expects($this->exactly(2))
			->method('getValue')
			->willReturnMap([
				['debug', '', false],
				['installed', '', true],
			]);
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())->method('getFolder')->with('awesomeapp')->willThrowException(new NotFoundException());
		$this->appData->expects($this->once())->method('newFolder')->with('awesomeapp')->willReturn($folder);
		$file = $this->createMock(ISimpleFile::class);
		$gzfile = $this->createMock(ISimpleFile::class);

		$fileDeps = $this->createMock(ISimpleFile::class);

		$folder->method('getFile')
			->willReturnCallback(function ($path) use ($file, $gzfile) {
				if ($path === 'combine.js') {
					return $file;
				} elseif ($path === 'combine.js.deps') {
					throw new NotFoundException();
				} elseif ($path === 'combine.js.gzip') {
					return $gzfile;
				}
				$this->fail();
			});
		$folder->expects($this->once())
			->method('newFile')
			->with('combine.js.deps')
			->willReturn($fileDeps);

		$actual = $this->jsCombiner->process(__DIR__, '/data/combine.json', 'awesomeapp');
		$this->assertTrue($actual);
	}

	public function testProcessUncachedFile(): void {
		$this->config
			->expects($this->exactly(2))
			->method('getValue')
			->willReturnMap([
				['debug', '', false],
				['installed', '', true],
			]);
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())->method('getFolder')->with('awesomeapp')->willReturn($folder);
		$file = $this->createMock(ISimpleFile::class);
		$fileDeps = $this->createMock(ISimpleFile::class);
		$gzfile = $this->createMock(ISimpleFile::class);

		$folder->method('getFile')
			->willReturnCallback(function ($path) use ($file, $gzfile) {
				if ($path === 'combine.js') {
					return $file;
				} elseif ($path === 'combine.js.deps') {
					throw new NotFoundException();
				} elseif ($path === 'combine.js.gzip') {
					return $gzfile;
				}
				$this->fail();
			});
		$folder->expects($this->once())
			->method('newFile')
			->with('combine.js.deps')
			->willReturn($fileDeps);

		$actual = $this->jsCombiner->process(__DIR__, '/data/combine.json', 'awesomeapp');
		$this->assertTrue($actual);
	}

	public function testProcessCachedFile(): void {
		$this->config
			->expects($this->exactly(2))
			->method('getValue')
			->willReturnMap([
				['debug', '', false],
				['installed', '', true],
			]);
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())->method('getFolder')->with('awesomeapp')->willReturn($folder);
		$file = $this->createMock(ISimpleFile::class);

		$fileDeps = $this->createMock(ISimpleFile::class);

		$fileDeps->expects($this->once())->method('getContent')->willReturn('{}');

		$folder->method('fileExists')
			->with('combine.js')
			->willReturn(true);

		$folder->method('getFile')
			->willReturnCallback(function ($path) use ($file, $fileDeps) {
				if ($path === 'combine.js') {
					return $file;
				}

				if ($path === 'combine.js.deps') {
					return $fileDeps;
				}

				$this->fail();
			});

		$actual = $this->jsCombiner->process(__DIR__, '/data/combine.json', 'awesomeapp');
		$this->assertTrue($actual);
	}

	public function testProcessCachedFileMemcache(): void {
		$this->config
			->expects($this->exactly(2))
			->method('getValue')
			->willReturnMap([
				['debug', '', false],
				['installed', '', true],
			]);
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->expects($this->once())
			->method('getFolder')
			->with('awesomeapp')
			->willReturn($folder);
		$folder->method('getName')
			->willReturn('awesomeapp');
		$folder->method('fileExists')
			->with('combine.js')
			->willReturn(true);

		$file = $this->createMock(ISimpleFile::class);

		$this->depsCache->method('get')
			->with('awesomeapp-combine.js.deps')
			->willReturn('{}');

		$folder->method('getFile')
			->willReturnCallback(function ($path) use ($file) {
				if ($path === 'combine.js') {
					return $file;
				}
				$this->fail();
			});

		$actual = $this->jsCombiner->process(__DIR__, '/data/combine.json', 'awesomeapp');
		$this->assertTrue($actual);
	}

	public function testIsCachedNoDepsFile(): void {
		$fileName = 'combine.json';
		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);

		$folder->method('getFile')
			->willReturnCallback(function ($path) use ($file) {
				if ($path === 'combine.js') {
					return $file;
				}
				if ($path === 'combine.js.deps') {
					throw new NotFoundException();
				}
				$this->fail();
			});

		$actual = self::invokePrivate($this->jsCombiner, 'isCached', [$fileName, $folder]);
		$this->assertFalse($actual);
	}

	public function testIsCachedWithNotExistingFile(): void {
		$fileName = 'combine.json';
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->method('fileExists')
			->with('combine.js')
			->willReturn(true);
		$file = $this->createMock(ISimpleFile::class);
		$folder->method('getFile')
			->with('combine.js.deps')
			->willReturn($file);
		$file->expects($this->once())
			->method('getContent')
			->willReturn(json_encode(['/etc/certainlynotexisting/file/ihope' => 10000]));

		$actual = self::invokePrivate($this->jsCombiner, 'isCached', [$fileName, $folder]);
		$this->assertFalse($actual);
	}

	public function testIsCachedWithOlderMtime(): void {
		$fileName = 'combine.json';
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->method('fileExists')
			->with('combine.js')
			->willReturn(true);
		$file = $this->createMock(ISimpleFile::class);
		$folder->method('getFile')
			->with('combine.js.deps')
			->willReturn($file);
		$file->expects($this->once())
			->method('getContent')
			->willReturn(json_encode([__FILE__ => 1234]));

		$actual = self::invokePrivate($this->jsCombiner, 'isCached', [$fileName, $folder]);
		$this->assertFalse($actual);
	}

	public function testIsCachedWithoutContent(): void {
		$fileName = 'combine.json';
		$folder = $this->createMock(ISimpleFolder::class);
		$folder->method('fileExists')
			->with('combine.js')
			->willReturn(true);
		$file = $this->createMock(ISimpleFile::class);
		$folder->method('getFile')
			->with('combine.js.deps')
			->willReturn($file);
		$file->expects($this->once())
			->method('getContent')
			->willReturn('');
		$this->logger->expects($this->once())
			->method('info')
			->with('JSCombiner: deps file empty: combine.js.deps');
		$actual = self::invokePrivate($this->jsCombiner, 'isCached', [$fileName, $folder]);
		$this->assertFalse($actual);
	}

	public function testCacheNoFile(): void {
		$fileName = 'combine.js';

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);
		$gzFile = $this->createMock(ISimpleFile::class);

		$path = __DIR__ . '/data/';

		$folder->method('getFile')->willThrowException(new NotFoundException());

		$folder->method('newFile')->willReturnCallback(
			function ($filename) use ($file, $depsFile, $gzFile) {
				if ($filename === 'combine.js') {
					return $file;
				} elseif ($filename === 'combine.js.deps') {
					return $depsFile;
				} elseif ($filename === 'combine.js.gzip') {
					return $gzFile;
				}
				$this->fail();
			}
		);

		$file->expects($this->once())->method('putContent');
		$depsFile->expects($this->once())->method('putContent');
		$gzFile->expects($this->once())->method('putContent');

		$actual = self::invokePrivate($this->jsCombiner, 'cache', [$path, 'combine.json', $folder]);
		$this->assertTrue($actual);
	}

	public function testCache(): void {
		$fileName = 'combine.js';

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);
		$gzFile = $this->createMock(ISimpleFile::class);

		$path = __DIR__ . '/data/';

		$folder->method('getFile')->willReturnCallback(
			function ($filename) use ($file, $depsFile, $gzFile) {
				if ($filename === 'combine.js') {
					return $file;
				} elseif ($filename === 'combine.js.deps') {
					return $depsFile;
				} elseif ($filename === 'combine.js.gzip') {
					return $gzFile;
				}
				$this->fail();
			}
		);

		$file->expects($this->once())->method('putContent');
		$depsFile->expects($this->once())->method('putContent');
		$gzFile->expects($this->once())->method('putContent');

		$actual = self::invokePrivate($this->jsCombiner, 'cache', [$path, 'combine.json', $folder]);
		$this->assertTrue($actual);
	}

	public function testCacheNotPermittedException(): void {
		$fileName = 'combine.js';

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);
		$gzFile = $this->createMock(ISimpleFile::class);

		$path = __DIR__ . '/data/';

		$folder->expects($this->exactly(3))
			->method('getFile')
			->willReturnMap([
				[$fileName, $file],
				[$fileName . '.deps', $depsFile],
				[$fileName . '.gzip', $gzFile]
			]);

		$file->expects($this->once())
			->method('putContent')
			->with('var a = \'hello\';


var b = \'world\';


');
		$depsFile
			->expects($this->once())
			->method('putContent')
			->with($this->callback(
				function ($content) {
					$deps = json_decode($content, true);
					return array_key_exists(__DIR__ . '/data//1.js', $deps)
						&& array_key_exists(__DIR__ . '/data//2.js', $deps);
				}))
			->willThrowException(new NotPermittedException());

		$actual = self::invokePrivate($this->jsCombiner, 'cache', [$path, 'combine.json', $folder]);
		$this->assertFalse($actual);
	}

	public function testCacheSuccess(): void {
		$fileName = 'combine.js';

		$folder = $this->createMock(ISimpleFolder::class);
		$file = $this->createMock(ISimpleFile::class);
		$depsFile = $this->createMock(ISimpleFile::class);
		$gzFile = $this->createMock(ISimpleFile::class);

		$path = __DIR__ . '/data/';


		$folder->method('getFile')->willReturnCallback(
			function ($filename) use ($file, $depsFile, $gzFile) {
				if ($filename === 'combine.js') {
					return $file;
				} elseif ($filename === 'combine.js.deps') {
					return $depsFile;
				} elseif ($filename === 'combine.js.gzip') {
					return $gzFile;
				}
				$this->fail();
			}
		);

		$file->expects($this->once())
			->method('putContent')
			->with('var a = \'hello\';


var b = \'world\';


');
		$depsFile->expects($this->once())->method('putContent')->with($this->callback(
			function ($content) {
				$deps = json_decode($content, true);
				return array_key_exists(__DIR__ . '/data//1.js', $deps)
					&& array_key_exists(__DIR__ . '/data//2.js', $deps);
			}));
		$gzFile->expects($this->once())->method('putContent')->with($this->callback(
			function ($content) {
				return gzdecode($content) === 'var a = \'hello\';


var b = \'world\';


';
			}
		));

		$actual = self::invokePrivate($this->jsCombiner, 'cache', [$path, 'combine.json', $folder]);
		$this->assertTrue($actual);
	}

	public static function dataGetCachedSCSS(): array {
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
	public function testGetCachedSCSS($appName, $fileName, $result): void {
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

	public function testGetContent(): void {
		// Create temporary file with some content
		$tmpFile = Server::get(ITempManager::class)->getTemporaryFile('JSCombinerTest');
		$pathInfo = pathinfo($tmpFile);
		file_put_contents($tmpFile, json_encode(['/foo/bar/test', $pathInfo['dirname'] . '/js/mytest.js']));
		$tmpFilePathArray = explode('/', $pathInfo['basename']);
		array_pop($tmpFilePathArray);

		$expected = [
			'//foo/bar/test',
			'/' . implode('/', $tmpFilePathArray) . $pathInfo['dirname'] . '/js/mytest.js',
		];
		$this->assertEquals($expected, $this->jsCombiner->getContent($pathInfo['dirname'], $pathInfo['basename']));
	}

	public function testGetContentInvalidJson(): void {
		// Create temporary file with some content
		$tmpFile = Server::get(ITempManager::class)->getTemporaryFile('JSCombinerTest');
		$pathInfo = pathinfo($tmpFile);
		file_put_contents($tmpFile, 'CertainlyNotJson');
		$expected = [];
		$this->assertEquals($expected, $this->jsCombiner->getContent($pathInfo['dirname'], $pathInfo['basename']));
	}

	public function testResetCache(): void {
		$file = $this->createMock(ISimpleFile::class);
		$file->expects($this->once())
			->method('delete');

		$folder = $this->createMock(ISimpleFolder::class);
		$folder->expects($this->once())
			->method('getDirectoryListing')
			->willReturn([$file]);

		$cache = $this->createMock(ICache::class);
		$this->cacheFactory->expects($this->once())
			->method('createDistributed')
			->willReturn($cache);
		$cache->expects($this->never())
			->method('clear');
		$this->appData->expects($this->once())
			->method('getDirectoryListing')
			->willReturn([$folder]);

		$this->jsCombiner->resetCache();
	}
}
