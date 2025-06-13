<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Utility;

use OC\AppFramework\Utility\SimpleContainer;
use OCP\AppFramework\QueryException;
use Psr\Container\NotFoundExceptionInterface;

interface TestInterface {
}

class ClassEmptyConstructor implements IInterfaceConstructor {
}

class ClassSimpleConstructor implements IInterfaceConstructor {
	public function __construct(
		public $test,
	) {
	}
}

class ClassComplexConstructor {
	public function __construct(
		public ClassSimpleConstructor $class,
		public $test,
	) {
	}
}

class ClassNullableUntypedConstructorArg {
	public function __construct(
		public $class,
	) {
	}
}
class ClassNullableTypedConstructorArg {
	public function __construct(
		public ?\Some\Class $class,
	) {
	}
}

interface IInterfaceConstructor {
}
class ClassInterfaceConstructor {
	public function __construct(
		public IInterfaceConstructor $class,
		public $test,
	) {
	}
}


class SimpleContainerTest extends \Test\TestCase {
	private $container;

	protected function setUp(): void {
		$this->container = new SimpleContainer();
	}



	public function testRegister(): void {
		$this->container->registerParameter('test', 'abc');
		$this->assertEquals('abc', $this->container->query('test'));
	}


	/**
	 * Test querying a class that is not registered without autoload enabled
	 */
	public function testNothingRegistered(): void {
		try {
			$this->container->query('something really hard', false);
			$this->fail('Expected `QueryException` exception was not thrown');
		} catch (\Throwable $exception) {
			$this->assertInstanceOf(QueryException::class, $exception);
			$this->assertInstanceOf(NotFoundExceptionInterface::class, $exception);
		}
	}


	/**
	 * Test querying a class that is not registered with autoload enabled
	 */
	public function testNothingRegistered_autoload(): void {
		try {
			$this->container->query('something really hard');
			$this->fail('Expected `QueryException` exception was not thrown');
		} catch (\Throwable $exception) {
			$this->assertInstanceOf(QueryException::class, $exception);
			$this->assertInstanceOf(NotFoundExceptionInterface::class, $exception);
		}
	}



	public function testNotAClass(): void {
		$this->expectException(QueryException::class);

		$this->container->query('Test\AppFramework\Utility\TestInterface');
	}


	public function testNoConstructorClass(): void {
		$object = $this->container->query('Test\AppFramework\Utility\ClassEmptyConstructor');
		$this->assertTrue($object instanceof ClassEmptyConstructor);
	}


	public function testInstancesOnlyOnce(): void {
		$object = $this->container->query('Test\AppFramework\Utility\ClassEmptyConstructor');
		$object2 = $this->container->query('Test\AppFramework\Utility\ClassEmptyConstructor');
		$this->assertSame($object, $object2);
	}

	public function testConstructorSimple(): void {
		$this->container->registerParameter('test', 'abc');
		$object = $this->container->query(
			'Test\AppFramework\Utility\ClassSimpleConstructor'
		);
		$this->assertTrue($object instanceof ClassSimpleConstructor);
		$this->assertEquals('abc', $object->test);
	}


	public function testConstructorComplex(): void {
		$this->container->registerParameter('test', 'abc');
		$object = $this->container->query(
			'Test\AppFramework\Utility\ClassComplexConstructor'
		);
		$this->assertTrue($object instanceof ClassComplexConstructor);
		$this->assertEquals('abc', $object->class->test);
		$this->assertEquals('abc', $object->test);
	}


	public function testConstructorComplexInterface(): void {
		$this->container->registerParameter('test', 'abc');
		$this->container->registerService(
			'Test\AppFramework\Utility\IInterfaceConstructor', function ($c) {
				return $c->query('Test\AppFramework\Utility\ClassSimpleConstructor');
			});
		$object = $this->container->query(
			'Test\AppFramework\Utility\ClassInterfaceConstructor'
		);
		$this->assertTrue($object instanceof ClassInterfaceConstructor);
		$this->assertEquals('abc', $object->class->test);
		$this->assertEquals('abc', $object->test);
	}


	public function testOverrideService(): void {
		$this->container->registerService(
			'Test\AppFramework\Utility\IInterfaceConstructor', function ($c) {
				return $c->query('Test\AppFramework\Utility\ClassSimpleConstructor');
			});
		$this->container->registerService(
			'Test\AppFramework\Utility\IInterfaceConstructor', function ($c) {
				return $c->query('Test\AppFramework\Utility\ClassEmptyConstructor');
			});
		$object = $this->container->query(
			'Test\AppFramework\Utility\IInterfaceConstructor'
		);
		$this->assertTrue($object instanceof ClassEmptyConstructor);
	}

	public function testRegisterAliasParamter(): void {
		$this->container->registerParameter('test', 'abc');
		$this->container->registerAlias('test1', 'test');
		$this->assertEquals('abc', $this->container->query('test1'));
	}

	public function testRegisterAliasService(): void {
		$this->container->registerService('test', function () {
			return new \StdClass;
		}, true);
		$this->container->registerAlias('test1', 'test');
		$this->assertSame(
			$this->container->query('test'), $this->container->query('test'));
		$this->assertSame(
			$this->container->query('test1'), $this->container->query('test1'));
		$this->assertSame(
			$this->container->query('test'), $this->container->query('test1'));
	}

	public static function sanitizeNameProvider(): array {
		return [
			['ABC\\Foo', 'ABC\\Foo'],
			['\\ABC\\Foo', '\\ABC\\Foo'],
			['\\ABC\\Foo', 'ABC\\Foo'],
			['ABC\\Foo', '\\ABC\\Foo'],
		];
	}

	/**
	 * @dataProvider sanitizeNameProvider
	 */
	public function testSanitizeName($register, $query): void {
		$this->container->registerService($register, function () {
			return 'abc';
		});
		$this->assertEquals('abc', $this->container->query($query));
	}


	public function testConstructorComplexNoTestParameterFound(): void {
		$this->expectException(QueryException::class);

		$object = $this->container->query(
			'Test\AppFramework\Utility\ClassComplexConstructor'
		);
		/* Use the object to trigger DI on PHP >= 8.4 */
		get_object_vars($object);
	}

	public function testRegisterFactory(): void {
		$this->container->registerService('test', function () {
			return new \StdClass();
		}, false);
		$this->assertNotSame(
			$this->container->query('test'), $this->container->query('test'));
	}

	public function testRegisterAliasFactory(): void {
		$this->container->registerService('test', function () {
			return new \StdClass();
		}, false);
		$this->container->registerAlias('test1', 'test');
		$this->assertNotSame(
			$this->container->query('test'), $this->container->query('test'));
		$this->assertNotSame(
			$this->container->query('test1'), $this->container->query('test1'));
		$this->assertNotSame(
			$this->container->query('test'), $this->container->query('test1'));
	}

	public function testQueryUntypedNullable(): void {
		$this->expectException(QueryException::class);

		$object = $this->container->query(
			ClassNullableUntypedConstructorArg::class
		);
		/* Use the object to trigger DI on PHP >= 8.4 */
		get_object_vars($object);
	}

	public function testQueryTypedNullable(): void {
		/** @var ClassNullableTypedConstructorArg $service */
		$service = $this->container->query(ClassNullableTypedConstructorArg::class);

		self::assertNull($service->class);
	}
}
