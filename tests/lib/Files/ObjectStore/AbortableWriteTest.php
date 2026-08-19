<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Icewind\Streams\CallbackWrapper;
use OC\Files\ObjectStore\StorageObjectStore;
use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Jail;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\GenericFileException;
use Test\TestCase;

/**
 * An object store only learns about a write when the stream is closed, so what sits
 * in the temporary file at that moment is what replaces the object. A writer that
 * gives up half way therefore has to say so, or the existing file is overwritten
 * with a partial copy.
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class AbortableWriteTest extends TestCase {
	private ObjectStoreStorageOverwrite $storage;

	protected function setUp(): void {
		parent::setUp();

		$this->storage = new ObjectStoreStorageOverwrite([
			'objectstore' => new StorageObjectStore(new Temporary()),
		]);
	}

	protected function tearDown(): void {
		$this->storage->getCache()->clear();
		parent::tearDown();
	}

	/**
	 * A source that stops part way through, the way a dropped upload connection or
	 * a short chunk during assembly does.
	 *
	 * @return resource
	 */
	private function failingSource(string $data, int $failAfter) {
		$source = fopen('php://temp', 'r+');
		fwrite($source, $data);
		rewind($source);

		$read = 0;
		return CallbackWrapper::wrap($source, function ($count) use (&$read, $failAfter): void {
			$read += $count;
			if ($read > $failAfter) {
				throw new \RuntimeException('connection lost mid write');
			}
		});
	}

	public function testAbortedWriteKeepsTheExistingObject(): void {
		$this->storage->file_put_contents('target.txt', 'the original content');

		$handle = $this->storage->fopen('target.txt', 'w');
		fwrite($handle, 'half a file');
		$this->storage->abortWrite('target.txt');
		fclose($handle);

		$this->assertEquals('the original content', $this->storage->file_get_contents('target.txt'));
		$this->assertEquals(strlen('the original content'), $this->storage->filesize('target.txt'));
	}

	public function testWriteStillCommitsWithoutAnAbort(): void {
		$this->storage->file_put_contents('target.txt', 'the original content');

		$handle = $this->storage->fopen('target.txt', 'w');
		fwrite($handle, 'the new content');
		fclose($handle);

		$this->assertEquals('the new content', $this->storage->file_get_contents('target.txt'));
		$this->assertEquals(strlen('the new content'), $this->storage->filesize('target.txt'));
	}

	/**
	 * Aborting one write must not stop an unrelated one that is open at the same time.
	 */
	public function testAbortOnlyAffectsTheGivenPath(): void {
		$this->storage->file_put_contents('aborted.txt', 'original one');
		$this->storage->file_put_contents('kept.txt', 'original two');

		$aborted = $this->storage->fopen('aborted.txt', 'w');
		$kept = $this->storage->fopen('kept.txt', 'w');
		fwrite($aborted, 'half a file');
		fwrite($kept, 'a complete file');

		$this->storage->abortWrite('aborted.txt');

		fclose($aborted);
		fclose($kept);

		$this->assertEquals('original one', $this->storage->file_get_contents('aborted.txt'));
		$this->assertEquals('a complete file', $this->storage->file_get_contents('kept.txt'));
	}

	/**
	 * A later write to the same path must still commit - aborting one write may not
	 * leave the path poisoned.
	 */
	public function testPathCanBeWrittenAgainAfterAnAbort(): void {
		$this->storage->file_put_contents('target.txt', 'the original content');

		$handle = $this->storage->fopen('target.txt', 'w');
		fwrite($handle, 'half a file');
		$this->storage->abortWrite('target.txt');
		fclose($handle);

		$handle = $this->storage->fopen('target.txt', 'w');
		fwrite($handle, 'the second attempt');
		fclose($handle);

		$this->assertEquals('the second attempt', $this->storage->file_get_contents('target.txt'));
	}

	/**
	 * Aborting when nothing is being written must not disturb the next write.
	 */
	public function testAbortWithoutAnOpenWriteDoesNothing(): void {
		$this->storage->file_put_contents('target.txt', 'the original content');
		$this->storage->abortWrite('target.txt');

		$handle = $this->storage->fopen('target.txt', 'w');
		fwrite($handle, 'the new content');
		fclose($handle);

		$this->assertEquals('the new content', $this->storage->file_get_contents('target.txt'));
	}

	/**
	 * The path a caller uses does not have to be spelled the way the storage stores
	 * it, so the abort has to normalize like fopen() does.
	 */
	public function testAbortMatchesTheWriteWhenThePathIsSpelledDifferently(): void {
		$this->storage->file_put_contents('target.txt', 'the original content');

		$handle = $this->storage->fopen('/target.txt', 'w');
		fwrite($handle, 'half a file');
		$this->storage->abortWrite('target.txt');
		fclose($handle);

		$this->assertEquals('the original content', $this->storage->file_get_contents('target.txt'));
	}

	/**
	 * The copy loop shared by the storage wrappers has to abort the write it opened
	 * when the source it is reading from fails.
	 */
	public function testWrapperWriteStreamFallbackKeepsTheExistingObject(): void {
		$this->storage->file_put_contents('target.txt', 'the original content');

		$wrapper = $this->buildFallbackWrapper($this->storage);

		try {
			$wrapper->writeStream('target.txt', $this->failingSource('a much longer new content', 3));
			$this->fail('Expected the failing source to propagate');
		} catch (\RuntimeException $e) {
			$this->assertEquals('connection lost mid write', $e->getMessage());
		}
		// the exception carries the failed write's stream in its stack trace, and
		// closing that stream is what hands the data to the object store: let go of
		// it so the test sees the state the file is really left in, not one that
		// still changes later in the request
		unset($e);
		gc_collect_cycles();

		$this->assertEquals('the original content', $this->storage->file_get_contents('target.txt'));
		$this->assertEquals(strlen('the original content'), $this->storage->filesize('target.txt'));
	}

	public function testWrapperWriteStreamFallbackStillCommitsAWholeStream(): void {
		$this->storage->file_put_contents('target.txt', 'the original content');

		$wrapper = $this->buildFallbackWrapper($this->storage);

		$source = fopen('php://temp', 'r+');
		fwrite($source, 'the new content');
		rewind($source);

		$this->assertEquals(strlen('the new content'), $wrapper->writeStream('target.txt', $source));
		$this->assertEquals('the new content', $this->storage->file_get_contents('target.txt'));
	}

	/**
	 * A jail hands its wrapped storage a different path than the one it was called
	 * with, so the abort has to be translated the same way the write was.
	 */
	public function testAbortIsTranslatedThroughAJail(): void {
		$this->storage->mkdir('jailed');
		$this->storage->file_put_contents('jailed/target.txt', 'the original content');

		$jail = $this->buildFallbackWrapper(new Jail([
			'storage' => $this->storage,
			'root' => 'jailed',
		]));

		try {
			$jail->writeStream('target.txt', $this->failingSource('a much longer new content', 3));
			$this->fail('Expected the failing source to propagate');
		} catch (\RuntimeException $e) {
			$this->assertEquals('connection lost mid write', $e->getMessage());
		}
		// see testWrapperWriteStreamFallbackKeepsTheExistingObject()
		unset($e);
		gc_collect_cycles();

		$this->assertEquals('the original content', $this->storage->file_get_contents('jailed/target.txt'));
	}

	/**
	 * A failed copy that reports itself by returning false rather than throwing has
	 * to discard the write just the same.
	 */
	public function testWrapperWriteStreamFallbackAbortsOnAFailedCopy(): void {
		$this->storage->file_put_contents('target.txt', 'the original content');

		$wrapper = $this->buildFallbackWrapper($this->storage);

		// a write-only handle cannot be read from, so the copy fails without throwing
		$source = fopen('php://temp', 'r+');
		fwrite($source, 'the new content');
		fclose($source);

		try {
			$wrapper->writeStream('target.txt', $source);
			$this->fail('Expected the failed copy to be reported');
		} catch (GenericFileException|\TypeError|\ValueError $e) {
			// expected: the copy could not be made
		}
		// see testWrapperWriteStreamFallbackKeepsTheExistingObject()
		unset($e);
		gc_collect_cycles();

		$this->assertEquals('the original content', $this->storage->file_get_contents('target.txt'));
	}

	/**
	 * A wrapper whose wrapped storage writes its stream itself is not used here, so
	 * force the copy loop that opens a write handle of its own.
	 */
	private function buildFallbackWrapper($storage): Wrapper {
		return new class(['storage' => $storage]) extends Wrapper {
			#[\Override]
			public function writeStream(string $path, $stream, ?int $size = null): int {
				return $this->writeStreamFallback($path, $stream);
			}
		};
	}
}
