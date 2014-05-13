<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC\AppFramework\Utility;


class ControllerMethodReflectorTest extends \PHPUnit_Framework_TestCase {


	/**
	 * @Annotation
	 */
	public function testReadAnnotation(){
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\OC\AppFramework\Utility\ControllerMethodReflectorTest',
			'testReadAnnotation'
		);

		$this->assertTrue($reader->hasAnnotation('Annotation'));
	}


	/**
	 * @Annotation
	 * @param test
	 */
	public function testReadAnnotationNoLowercase(){
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\OC\AppFramework\Utility\ControllerMethodReflectorTest',
			'testReadAnnotationNoLowercase'
		);

		$this->assertTrue($reader->hasAnnotation('Annotation'));
		$this->assertFalse($reader->hasAnnotation('param'));
	}


	/**
	 * @Annotation
	 * @param int $test
	 */
	public function testReadTypeIntAnnotations(){
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\OC\AppFramework\Utility\ControllerMethodReflectorTest',
			'testReadTypeIntAnnotations'
		);

		$this->assertEquals('int', $reader->getType('test'));
	}


	/**
	 * @Annotation
	 * @param double $test something special
	 */
	public function testReadTypeDoubleAnnotations(){
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\OC\AppFramework\Utility\ControllerMethodReflectorTest',
			'testReadTypeDoubleAnnotations'
		);

		$this->assertEquals('double', $reader->getType('test'));
	}


	public function arguments($arg, $arg2='hi') {}
	public function testReflectParameters() {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\OC\AppFramework\Utility\ControllerMethodReflectorTest',
			'arguments'
		);

		$this->assertEquals(array('arg' => null, 'arg2' => 'hi'), $reader->getParameters());	
	}


	public function arguments2($arg) {}
	public function testReflectParameters2() {
		$reader = new ControllerMethodReflector();
		$reader->reflect(
			'\OC\AppFramework\Utility\ControllerMethodReflectorTest',
			'arguments2'
		);

		$this->assertEquals(array('arg' => null), $reader->getParameters());	
	}


}
