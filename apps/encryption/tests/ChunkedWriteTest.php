<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\encryption\tests;

use OC\Files\Storage\Temporary;
use OC\Files\View;
use OCA\Encryption\KeyManager;
use OCP\Server;
use Test\TestCase;
use Test\Traits\EncryptionTrait;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Writing through a stream does not update the file cache - that is left to the
 * caller. Until it does, neither the encrypted version nor the unencrypted size
 * of the written file can be read from the cache, but both are part of the block
 * signatures and have to match on the next read.
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ChunkedWriteTest extends TestCase {
	use MountProviderTrait;
	use EncryptionTrait;
	use UserTrait;

	private function setUpView(): View {
		Server::get(KeyManager::class)->validateMasterKey();
		Server::get(KeyManager::class)->validateShareKey();
		$this->createUser('test1', 'test2');
		$this->setupForUser('test1', 'test2');
		$this->registerMount('test1', new Temporary(), '/test1/files/other');
		$this->loginWithEncryption('test1');

		return new View('/test1/files');
	}

	/**
	 * The unencrypted block size is 6072 bytes, so the chunks cover writes inside
	 * a single block, across a block boundary and on a block boundary.
	 *
	 * @return array<string, array{int[]}>
	 */
	public static function chunkSizesProvider(): array {
		return [
			'several chunks in one block' => [[100, 100, 100]],
			'chunks crossing a block' => [[4000, 4000]],
			'chunks of varying size' => [[1000, 2000, 3000, 4000, 5000]],
			'a single full block' => [[6072]],
			'a full block in two chunks' => [[3000, 3072]],
			'two full blocks' => [[6072, 6072]],
			'a full block and one byte' => [[6072, 1]],
			'chunks larger than a block' => [[8192, 8192, 8192]],
		];
	}

	/**
	 * @param int[] $chunks
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('chunkSizesProvider')]
	public function testReadBackFileWrittenInChunks(array $chunks): void {
		$view = $this->setUpView();
		$source = self::getUniqueID('source') . '.bin';

		$expected = $this->writeInChunks($view, $source, $chunks);

		$this->assertEquals(strlen($expected), $view->filesize($source));
		$this->assertEquals($expected, $view->file_get_contents($source));
	}

	/**
	 * @param int[] $chunks
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('chunkSizesProvider')]
	public function testCopyFileWrittenInChunks(array $chunks): void {
		$view = $this->setUpView();
		$source = self::getUniqueID('source') . '.bin';
		$target = self::getUniqueID('target') . '.bin';

		$expected = $this->writeInChunks($view, $source, $chunks);

		$this->assertTrue($view->copy($source, $target));
		$this->assertEquals($expected, $view->file_get_contents($target));
	}

	/**
	 * A part file is never in the file cache. With `part_file_in_storage`
	 * disabled it is written to the user home while the target can live on
	 * another storage, in which case moving it over has to read it back.
	 */
	public function testMovePartFileToAnotherStorage(): void {
		$view = $this->setUpView();

		$partFile = self::getUniqueID() . '.ocTransferId1.part';
		$target = 'other/' . self::getUniqueID('target') . '.bin';

		$expected = $this->writeInChunks($view, $partFile, [8192, 8192, 8192]);

		[$partStorage, $internalPartPath] = $view->resolvePath($partFile);
		[$targetStorage, $internalTargetPath] = $view->resolvePath($target);
		$this->assertTrue($targetStorage->moveFromStorage($partStorage, $internalPartPath, $internalTargetPath));

		$this->assertEquals($expected, $view->file_get_contents($target));
	}

	/**
	 * @param int[] $chunks
	 * @return string the written content
	 */
	private function writeInChunks(View $view, string $path, array $chunks): string {
		$content = '';
		$handle = $view->fopen($path, 'w');
		$this->assertIsResource($handle);
		foreach ($chunks as $index => $length) {
			$chunk = str_repeat((string)($index % 10), $length);
			$content .= $chunk;
			$this->assertEquals($length, fwrite($handle, $chunk));
		}
		fclose($handle);

		return $content;
	}
}
