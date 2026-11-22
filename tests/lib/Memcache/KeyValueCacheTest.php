<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Memcache;

use OC\Memcache\KeyValueCache;
use OCP\IConfig;
use OCP\Server;
use Predis\CommunicationException;

#[\PHPUnit\Framework\Attributes\Group('Memcache')]
#[\PHPUnit\Framework\Attributes\Group('KeyValueCache')]
class KeyValueCacheTest extends Cache {
	/**
	 * @var KeyValueCache cache;
	 */
	protected $instance;

	#[\Override]
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if (Server::get(IConfig::class)->getSystemValue('memcache.kvstore', []) === []) {
			self::markTestSkipped('Key-value store not configured in config.php');
		}

		if (!KeyValueCache::isAvailable()) {
			self::markTestSkipped('The predis library is not available.');
		}

		try {
			$instance = new KeyValueCache(self::getUniqueID());
			if ($instance->set(self::getUniqueID(), self::getUniqueID()) === false) {
				self::markTestSkipped('Key-value store server seems to be down.');
			}
		} catch (CommunicationException $e) {
			self::markTestSkipped('Key-value store server is not reachable: ' . $e->getMessage());
		}
	}

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->instance = new KeyValueCache($this->getUniqueID());
	}

	/**
	 * @return array<string, array{0: mixed}>
	 */
	public static function roundtripValuesProvider(): array {
		return [
			'string' => ['some string value'],
			'empty string' => [''],
			'numeric string' => ['0123'],
			'integer' => [1234],
			'zero' => [0],
			'negative integer' => [-42],
			'boolean true' => [true],
			'boolean false' => [false],
			'null' => [null],
			'list' => [['a', 'b', 'c']],
			'associative array' => [['foo' => 'bar', 'baz' => 42]],
			'nested array' => [['a' => ['b' => ['c' => 'd']]]],
		];
	}

	/**
	 * A value that is written to the cache must be readable again unchanged.
	 *
	 * Runs against every configured topology (single server, Sentinel and
	 * cluster) through the CI matrix, see .github/workflows/phpunit-kvstore.yml.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('roundtripValuesProvider')]
	public function testStoreAndReadBack(mixed $value): void {
		$this->assertNull($this->instance->get('roundtrip'), 'key should be empty before storing');

		$this->assertNotFalse($this->instance->set('roundtrip', $value), 'value should be stored');
		$this->assertTrue($this->instance->hasKey('roundtrip'), 'stored key should exist');
		$this->assertEquals($value, $this->instance->get('roundtrip'), 'stored value should be read back unchanged');
	}

	public function testScriptHashes(): void {
		$scripts = self::invokePrivate($this->instance, 'LUA_SCRIPTS');
		foreach ($scripts as $script) {
			$this->assertEquals(sha1($script[0]), $script[1]);
		}
	}

	public function testCasTtlNotChanged(): void {
		$this->instance->set('foo', 'bar', 50);
		$this->assertTrue($this->instance->compareSetTTL('foo', 'bar', 100));
		// allow for 1s of inaccuracy due to time moving forward
		$this->assertLessThan(1, 100 - $this->instance->getTTL('foo'));
	}

	public function testCasTtlChanged(): void {
		$this->instance->set('foo', 'bar1', 50);
		$this->assertFalse($this->instance->compareSetTTL('foo', 'bar', 100));
		// allow for 1s of inaccuracy due to time moving forward
		$this->assertLessThan(1, 50 - $this->instance->getTTL('foo'));
	}
}
