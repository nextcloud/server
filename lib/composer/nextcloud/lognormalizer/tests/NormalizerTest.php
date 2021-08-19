<?php

/**
 * interfaSys - lognormalizer
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Olivier Paroz <dev-lognormalizer@interfasys.ch>
 * @author Jordi Boggiano <j.boggiano@seld.be>
 *
 * @copyright Olivier Paroz 2015
 * @copyright Jordi Boggiano 2014-2015
 */

namespace Nextcloud\LogNormalizer;

use Exception;
use PHPUnit\Framework\TestCase;
use TypeError;
use function get_class;

class NormalizerTest extends TestCase {

	/**
	 * @var Normalizer
	 */
	protected $normalizer;

	protected function setUp(): void {
		$this->normalizer = new Normalizer();
	}

	/**
	 * Using format() directly to make sure it doesn't modify strings
	 */
	public function testString() {
		$data = "Don't underestimate the power of the string [+*%&]";
		$formatted = $this->normalizer->format($data);

		self::assertEquals("Don't underestimate the power of the string [+*%&]", $formatted);
	}

	public function testBoolean() {
		$data = true;
		$normalized = $this->normalizer->normalize($data);

		self::assertTrue($normalized);

		$formatted = $this->normalizer->convertToString($normalized);

		self::assertEquals('true', $formatted);
	}

	public function testFloat() {
		$data = 3.14413;
		$normalized = $this->normalizer->normalize($data);
		self::assertIsFloat($normalized);

		$formatted = $this->normalizer->convertToString($normalized);

		self::assertIsString($formatted);
		self::assertEquals(3.14413, $formatted);
	}

	public function testInfinity() {
		$data = [
			'inf'  => INF,
			'-inf' => -INF,
		];
		$normalized = $this->normalizer->normalize($data);

		self::assertEquals($data, $normalized);

		$formatted = $this->normalizer->convertToString($normalized);

		self::assertEquals('{"inf":"INF","-inf":"-INF"}', $formatted);
	}

	public function testNan() {
		$data = acos(4);
		$normalized = $this->normalizer->normalize($data);

		self::assertEquals('NaN', $normalized);
	}

	public function testSimpleObject() {
		$data = new TestFooNorm();
		$normalized = $this->normalizer->normalize($data);

		$expectedResult = [
			'[object] (Nextcloud\\LogNormalizer\\TestFooNorm)' => ['foo' => 'foo']
		];
		self::assertEquals($expectedResult, $normalized);

		$formatted = $this->normalizer->convertToString($normalized);
		$expectedString = '{"[object] (Nextcloud\\LogNormalizer\\TestFooNorm)":{"foo":"foo"}}';

		self::assertEquals($expectedString, $formatted);
	}

	public function testLongArray() {
		$keys = range(0, 25);
		$data = array_fill_keys($keys, 'normalizer');

		$normalizer = new Normalizer(4, 20);
		$normalized = $normalizer->normalize($data);

		$expectedResult = array_slice($data, 0, 19);
		$expectedResult['...'] = 'Over 20 items, aborting normalization';

		self::assertEquals($expectedResult, $normalized);
	}

	public function testArrayWithObject() {
		$objectFoo = new TestFooNorm;
		$data = [
			'foo' => $objectFoo,
			'baz' => [
				'quz',
				true
			]
		];

		$normalized = $this->normalizer->normalize($data);

		$objectFooName = get_class($objectFoo);
		$objectFooResult = [
			'[object] (' . $objectFooName . ')' => ['foo' => 'foo']
		];

		self::assertEquals($objectFooResult, $normalized['foo']);

		$expectedResult = [
			"foo" => $objectFooResult,
			"baz" => [
				"quz",
				true
			]
		];
		self::assertEquals($expectedResult, $normalized);

		$formatted = $this->normalizer->convertToString($normalized);
		$objectFooName = get_class($objectFoo);
		$objectFooResult = '{"[object] (' . $objectFooName . ')":{"foo":"foo"}}';
		$expectedString =
			'{"foo":' . $objectFooResult . ',"baz":["quz",true]}';

		self::assertEquals($expectedString, $formatted);
	}

	public function testUnlimitedObjectRecursion() {
		$objectMain = new TestEmbeddedObjects;
		$objectFoo = new TestFooNorm;
		$objectBar = new TestBarNorm;
		$objectBaz = new TestBazNorm;

		$data = $objectMain;
		$normalizer = new Normalizer(20, 20, 'Y-m-d');
		$normalized = $normalizer->normalize($data);

		$objectMainName = get_class($objectMain);
		$objectFooName = get_class($objectFoo);
		$objectBarName = get_class($objectBar);
		$objectBazName = get_class($objectBaz);

		$objectFooResult = [
			'[object] (' . $objectFooName . ')' => ['foo' => 'foo']
		];

		self::assertEquals(
			$objectFooResult, $normalized['[object] (' . $objectMainName . ')']['foo']
		);

		$objectBarResult = [
			'[object] (' . $objectBarName . ')' => [
				'foo' => $objectFooResult,
				'bar' => 'bar'
			]
		];

		self::assertEquals(
			$objectBarResult, $normalized['[object] (' . $objectMainName . ')']['bar']
		);

		$objectBazResult = [
			'[object] (' . $objectBazName . ')' => [
				'foo' => $objectFooResult,
				'bar' => $objectBarResult,
				'baz' => 'baz'
			]
		];

		self::assertEquals(
			$objectBazResult, $normalized['[object] (' . $objectMainName . ')']['baz']
		);

		$objectMainResult = [
			'[object] (' . $objectMainName . ')' => [
				'foo'    => $objectFooResult,
				'bar'    => $objectBarResult,
				'baz'    => $objectBazResult,
				'fooBar' => 'foobar'
			]
		];

		self::assertEquals($objectMainResult, $normalized);
	}

	public function testLimitedObjectRecursion() {
		$objectMain = new TestEmbeddedObjects;
		$objectFoo = new TestFooNorm;
		$objectBar = new TestBarNorm;
		$objectBaz = new TestBazNorm;

		$data = $objectMain;
		$normalizer = new Normalizer(4, 20, 'Y-m-d');
		$normalized = $normalizer->normalize($data);

		$objectMainName = get_class($objectMain);
		$objectFooName = get_class($objectFoo);
		$objectBarName = get_class($objectBar);
		$objectBazName = get_class($objectBaz);

		$objectFooResult = [
			'[object] (' . $objectFooName . ')' => ['foo' => 'foo']
		];

		self::assertEquals(
			$objectFooResult, $normalized['[object] (' . $objectMainName . ')']['foo']
		);

		// Already reaching the default limit
		$objectBarResult = [
			'[object] (' . $objectBarName . ')' => [
				'foo' => '[object] (' . $objectFooName . ')',
				'bar' => 'bar'
			]
		];

		self::assertEquals(
			$objectBarResult, $normalized['[object] (' . $objectMainName . ')']['bar']
		);


		// At this stage, we can't inspect deeper objects
		$objectBazResult = [
			'[object] (' . $objectBazName . ')' => [
				'foo' => '[object] (' . $objectFooName . ')',
				'bar' => '[object] (' . $objectBarName . ')',
				'baz' => 'baz'
			]
		];

		self::assertEquals(
			$objectBazResult, $normalized['[object] (' . $objectMainName . ')']['baz']
		);

		$objectMainResult = [
			'[object] (' . $objectMainName . ')' => [
				'foo'    => $objectFooResult,
				'bar'    => $objectBarResult,
				'baz'    => $objectBazResult,
				'fooBar' => 'foobar'
			]
		];

		self::assertEquals($objectMainResult, $normalized);
	}

	public function testDate() {
		$normalizer = new Normalizer(2, 20, 'Y-m-d');
		$data = new \DateTime;
		$normalized = $normalizer->normalize($data);

		self::assertEquals(date('Y-m-d'), $normalized);
	}

	public function testResource() {
		$data = fopen('php://memory', 'rb');
		$resourceId = (int)$data;
		$normalized = $this->normalizer->normalize($data);

		self::assertEquals('[resource] Resource id #' . $resourceId, $normalized);
	}

	public function testFormatExceptions() {
		$e = new \LogicException('bar');
		$e2 = new \RuntimeException('foo', 0, $e);
		$data = [
			'exception' => $e2,
		];
		$normalized = $this->normalizer->normalize($data);

		self::assertTrue(isset($normalized['exception']['previous']));
		unset($normalized['exception']['previous']);

		self::assertEquals(
			[
				'exception' => [
					'class'   => get_class($e2),
					'message' => $e2->getMessage(),
					'code'    => $e2->getCode(),
					'file'    => $e2->getFile() . ':' . $e2->getLine(),
					'trace'   => $e->getTraceAsString(),
				]
			], $normalized
		);
	}

	public function testFormatExceptionWithPreviousThrowable() {
		$t = new TypeError("not a type error");
		$e = new Exception("an exception", 13, $t);

		$normalized = $this->normalizer->normalize([
			'exception' => $e,
		]);

		self::assertEquals(
			[
				'exception' => [
					'class'   => get_class($e),
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
					'file'    => $e->getFile() . ':' . $e->getLine(),
					'trace'   => $e->getTraceAsString(),
					'previous' => [
						'class' => 'TypeError',
						'message' => 'not a type error',
						'code' => 0,
						'file' => $t->getFile() . ':' . $t->getLine(),
						'trace' => $t->getTraceAsString(),
					]
				]
			], $normalized
		);
		self::assertTrue(isset($normalized['exception']['previous']));
	}

	public function testUnknown() {
		$data = fopen('php://memory', 'rb');
		fclose($data);
		$normalized = $this->normalizer->normalize($data);

		self::assertEquals('[unknown(' . gettype($data) . ')]', $normalized);
	}
}

class TestFooNorm {
	public $foo = 'foo';
}

class TestBarNorm {
	public $foo;
	public $bar = 'bar';

	public function __construct() {
		$this->foo = new TestFooNorm();
	}
}

class TestBazNorm {
	public $foo;
	public $bar;

	public $baz = 'baz';

	public function __construct() {
		$this->foo = new TestFooNorm();
		$this->bar = new TestBarNorm();
	}
}

class TestEmbeddedObjects {
	public $foo;
	public $bar;
	public $baz;
	public $fooBar;

	public function __construct() {
		$this->foo = new TestFooNorm();
		$this->bar = new TestBarNorm();
		$this->baz = new TestBazNorm();
		$this->methodOne();
	}

	public function methodOne() {
		$this->fooBar = $this->foo->foo . $this->bar->bar;
	}
}
