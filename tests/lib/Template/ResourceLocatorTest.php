<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Template;

use OC\SystemConfig;
use OC\Template\ResourceNotFoundException;
use Psr\Log\LoggerInterface;

class ResourceLocatorTest extends \Test\TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject */
	protected $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->createMock(LoggerInterface::class);
	}

	/**
	 * @param string $theme
	 * @return \PHPUnit\Framework\MockObject\MockObject
	 */
	public function getResourceLocator($theme) {
		$systemConfig = $this->createMock(SystemConfig::class);
		$systemConfig
			->expects($this->any())
			->method('getValue')
			->with('theme', '')
			->willReturn($theme);
		$this->overwriteService(SystemConfig::class, $systemConfig);
		return $this->getMockForAbstractClass('OC\Template\ResourceLocator',
			[$this->logger],
			'', true, true, true, []);
	}

	public function testFind(): void {
		$locator = $this->getResourceLocator('theme');
		$locator->expects($this->once())
			->method('doFind')
			->with('foo');
		$locator->expects($this->once())
			->method('doFindTheme')
			->with('foo');
		/** @var \OC\Template\ResourceLocator $locator */
		$locator->find(['foo']);
	}

	public function testFindNotFound(): void {
		$systemConfig = $this->createMock(SystemConfig::class);
		$systemConfig->method('getValue')
			->with('theme', '')
			->willReturn('theme');
		$this->overwriteService(SystemConfig::class, $systemConfig);
		$locator = $this->getResourceLocator('theme',
			['core' => 'map'], ['3rd' => 'party'], ['foo' => 'bar']);
		$locator->expects($this->once())
			->method('doFind')
			->with('foo')
			->willThrowException(new ResourceNotFoundException('foo', 'map'));
		$locator->expects($this->once())
			->method('doFindTheme')
			->with('foo')
			->willThrowException(new ResourceNotFoundException('foo', 'map'));
		$this->logger->expects($this->exactly(2))
			->method('debug')
			->with($this->stringContains('map/foo'));
		/** @var \OC\Template\ResourceLocator $locator */
		$locator->find(['foo']);
	}

	public function testAppendIfExist(): void {
		$locator = $this->getResourceLocator('theme');
		/** @var \OC\Template\ResourceLocator $locator */
		$method = new \ReflectionMethod($locator, 'appendIfExist');
		$method->setAccessible(true);

		$method->invoke($locator, __DIR__, basename(__FILE__), 'webroot');
		$resource1 = [__DIR__, 'webroot', basename(__FILE__)];
		$this->assertEquals([$resource1], $locator->getResources());

		$method->invoke($locator, __DIR__, 'does-not-exist');
		$this->assertEquals([$resource1], $locator->getResources());
	}
}
