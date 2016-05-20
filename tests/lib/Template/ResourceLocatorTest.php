<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Template;

use OC\Template\ResourceNotFoundException;

class ResourceLocatorTest extends \Test\TestCase {
	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $logger;

	protected function setUp() {
		parent::setUp();
		$this->logger = $this->getMock('OCP\ILogger');
	}

	/**
	 * @param string $theme
	 * @param array $core_map
	 * @param array $party_map
	 * @param array $appsRoots
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	public function getResourceLocator($theme, $core_map, $party_map, $appsRoots) {
		return $this->getMockForAbstractClass('OC\Template\ResourceLocator',
			array($this->logger, $theme, $core_map, $party_map, $appsRoots ),
			'', true, true, true, array());
	}

	public function testConstructor() {
		$locator = $this->getResourceLocator('theme',
			array('core'=>'map'), array('3rd'=>'party'), array('foo'=>'bar'));
		$this->assertAttributeEquals('theme', 'theme', $locator);
		$this->assertAttributeEquals('core', 'serverroot', $locator);
		$this->assertAttributeEquals(array('core'=>'map','3rd'=>'party'), 'mapping', $locator);
		$this->assertAttributeEquals('3rd', 'thirdpartyroot', $locator);
		$this->assertAttributeEquals('map', 'webroot', $locator);
		$this->assertAttributeEquals(array(), 'resources', $locator);
	}

	public function testFind() {
		$locator = $this->getResourceLocator('theme',
			array('core' => 'map'), array('3rd' => 'party'), array('foo' => 'bar'));
		$locator->expects($this->once())
			->method('doFind')
			->with('foo');
		$locator->expects($this->once())
			->method('doFindTheme')
			->with('foo');
		/** @var \OC\Template\ResourceLocator $locator */
		$locator->find(array('foo'));
	}

	public function testFindNotFound() {
		$locator = $this->getResourceLocator('theme',
			array('core'=>'map'), array('3rd'=>'party'), array('foo'=>'bar'));
		$locator->expects($this->once())
			->method('doFind')
			->with('foo')
			->will($this->throwException(new ResourceNotFoundException('foo', 'map')));
		$locator->expects($this->once())
			->method('doFindTheme')
			->with('foo')
			->will($this->throwException(new ResourceNotFoundException('foo', 'map')));
		$this->logger->expects($this->exactly(2))
			->method('error')
			->with($this->stringContains('map/foo'));
		/** @var \OC\Template\ResourceLocator $locator */
		$locator->find(array('foo'));
	}

	public function testAppendIfExist() {
		$locator = $this->getResourceLocator('theme',
			array(__DIR__=>'map'), array('3rd'=>'party'), array('foo'=>'bar'));
		/** @var \OC\Template\ResourceLocator $locator */
		$method = new \ReflectionMethod($locator, 'appendIfExist');
		$method->setAccessible(true);

		$method->invoke($locator, __DIR__, basename(__FILE__), 'webroot');
		$resource1 = array(__DIR__, 'webroot', basename(__FILE__));
		$this->assertEquals(array($resource1), $locator->getResources());

		$method->invoke($locator, __DIR__, basename(__FILE__));
		$resource2 = array(__DIR__, 'map', basename(__FILE__));
		$this->assertEquals(array($resource1, $resource2), $locator->getResources());

		$method->invoke($locator, __DIR__, 'does-not-exist');
		$this->assertEquals(array($resource1, $resource2), $locator->getResources());
	}
}
