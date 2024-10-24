<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\AppFramework\Utility;

use OC\AppFramework\Utility\ControllerMethodReflector;

class BaseController {
	/**
	 * @Annotation
	 */
	public function test() {
	}

	/**
	 * @Annotation
	 */
	public function test2() {
	}

	/**
	 * @Annotation
	 */
	public function test3() {
	}
}

class MiddleController extends BaseController {
	/**
	 * @NoAnnotation
	 */
	public function test2() {
	}

	public function test3() {
	}

	/**
	 * @psalm-param int<-4, 42> $rangedOne
	 * @psalm-param int<min, max> $rangedTwo
	 * @return void
	 */
	public function test4(int $rangedOne, int $rangedTwo) {
	}
}

class EndController extends MiddleController {
}

class ControllerMethodReflectorTest extends \Test\TestCase {
	/**
	 * @Annotation
	 */
	public function testReadAnnotation(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\Test\AppFramework\Utility\ControllerMethodReflectorTest',
			'testReadAnnotation'
		);

		$this->assertTrue($reader->hasAnnotation('Annotation'));
	}

	/**
	 * @Annotation(parameter=value)
	 */
	public function testGetAnnotationParameterSingle(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			self::class,
			__FUNCTION__
		);

		$this->assertSame('value', $reader->getAnnotationParameter('Annotation', 'parameter'));
	}

	/**
	 * @Annotation(parameter1=value1, parameter2=value2,parameter3=value3)
	 */
	public function testGetAnnotationParameterMultiple(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			self::class,
			__FUNCTION__
		);

		$this->assertSame('value1', $reader->getAnnotationParameter('Annotation', 'parameter1'));
		$this->assertSame('value2', $reader->getAnnotationParameter('Annotation', 'parameter2'));
		$this->assertSame('value3', $reader->getAnnotationParameter('Annotation', 'parameter3'));
	}

	/**
	 * @Annotation
	 * @param test
	 */
	public function testReadAnnotationNoLowercase(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\Test\AppFramework\Utility\ControllerMethodReflectorTest',
			'testReadAnnotationNoLowercase'
		);

		$this->assertTrue($reader->hasAnnotation('Annotation'));
		$this->assertFalse($reader->hasAnnotation('param'));
	}


	/**
	 * @Annotation
	 * @param int $test
	 */
	public function testReadTypeIntAnnotations(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\Test\AppFramework\Utility\ControllerMethodReflectorTest',
			'testReadTypeIntAnnotations'
		);

		$this->assertEquals('int', $reader->getType('test'));
	}

	/**
	 * @Annotation
	 * @param int $a
	 * @param int $b
	 */
	public function arguments3($a, float $b, int $c, $d) {
	}

	/**
	 * @requires PHP 7
	 */
	public function testReadTypeIntAnnotationsScalarTypes(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\Test\AppFramework\Utility\ControllerMethodReflectorTest',
			'arguments3'
		);

		$this->assertEquals('int', $reader->getType('a'));
		$this->assertEquals('float', $reader->getType('b'));
		$this->assertEquals('int', $reader->getType('c'));
		$this->assertNull($reader->getType('d'));
	}


	/**
	 * @Annotation
	 * @param double $test something special
	 */
	public function testReadTypeDoubleAnnotations(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\Test\AppFramework\Utility\ControllerMethodReflectorTest',
			'testReadTypeDoubleAnnotations'
		);

		$this->assertEquals('double', $reader->getType('test'));
	}

	/**
	 * @Annotation
	 * @param string $foo
	 */
	public function testReadTypeWhitespaceAnnotations(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\Test\AppFramework\Utility\ControllerMethodReflectorTest',
			'testReadTypeWhitespaceAnnotations'
		);

		$this->assertEquals('string', $reader->getType('foo'));
	}


	public function arguments($arg, $arg2 = 'hi') {
	}
	public function testReflectParameters(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\Test\AppFramework\Utility\ControllerMethodReflectorTest',
			'arguments'
		);

		$this->assertEquals(['arg' => null, 'arg2' => 'hi'], $reader->getParameters());
	}


	public function arguments2($arg) {
	}
	public function testReflectParameters2(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\Test\AppFramework\Utility\ControllerMethodReflectorTest',
			'arguments2'
		);

		$this->assertEquals(['arg' => null], $reader->getParameters());
	}


	public function testInheritance(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect('Test\AppFramework\Utility\EndController', 'test');

		$this->assertTrue($reader->hasAnnotation('Annotation'));
	}


	public function testInheritanceOverride(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect('Test\AppFramework\Utility\EndController', 'test2');

		$this->assertTrue($reader->hasAnnotation('NoAnnotation'));
		$this->assertFalse($reader->hasAnnotation('Annotation'));
	}


	public function testInheritanceOverrideNoDocblock(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect('Test\AppFramework\Utility\EndController', 'test3');

		$this->assertFalse($reader->hasAnnotation('Annotation'));
	}

	public function testRangeDetection(): void {
		$reader = new ControllerMethodReflector();
		$reader->reflect('Test\AppFramework\Utility\EndController', 'test4');

		$rangeInfo1 = $reader->getRange('rangedOne');
		$this->assertSame(-4, $rangeInfo1['min']);
		$this->assertSame(42, $rangeInfo1['max']);

		$rangeInfo2 = $reader->getRange('rangedTwo');
		$this->assertSame(PHP_INT_MIN, $rangeInfo2['min']);
		$this->assertSame(PHP_INT_MAX, $rangeInfo2['max']);
	}
}
