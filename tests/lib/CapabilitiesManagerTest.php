<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test;

use OC\CapabilitiesManager;
use OCP\AppFramework\QueryException;
use OCP\Capabilities\ICapability;
use OCP\Capabilities\IPublicCapability;
use Psr\Log\LoggerInterface;

class CapabilitiesManagerTest extends TestCase {
	/** @var CapabilitiesManager */
	private $manager;

	/** @var LoggerInterface */
	private $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$this->manager = new CapabilitiesManager($this->logger);
	}

	/**
	 * Test no capabilities
	 */
	public function testNoCapabilities(): void {
		$res = $this->manager->getCapabilities();
		$this->assertEmpty($res);
	}

	/**
	 * Test a valid capabilitie
	 */
	public function testValidCapability(): void {
		$this->manager->registerCapability(function () {
			return new SimpleCapability();
		});

		$res = $this->manager->getCapabilities();
		$this->assertEquals(['foo' => 1], $res);
	}

	/**
	 * Test a public capabilitie
	 */
	public function testPublicCapability(): void {
		$this->manager->registerCapability(function () {
			return new PublicSimpleCapability1();
		});
		$this->manager->registerCapability(function () {
			return new SimpleCapability2();
		});
		$this->manager->registerCapability(function () {
			return new SimpleCapability3();
		});

		$res = $this->manager->getCapabilities(true);
		$this->assertEquals(['foo' => 1], $res);
	}

	/**
	 * Test that we need something that implents ICapability
	 */
	public function testNoICapability(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('The given Capability (Test\\NoCapability) does not implement the ICapability interface');

		$this->manager->registerCapability(function () {
			return new NoCapability();
		});

		$res = $this->manager->getCapabilities();
		$this->assertEquals([], $res);
	}

	/**
	 * Test a bunch of merged Capabilities
	 */
	public function testMergedCapabilities(): void {
		$this->manager->registerCapability(function () {
			return new SimpleCapability();
		});
		$this->manager->registerCapability(function () {
			return new SimpleCapability2();
		});
		$this->manager->registerCapability(function () {
			return new SimpleCapability3();
		});

		$res = $this->manager->getCapabilities();
		$expected = [
			'foo' => 1,
			'bar' => [
				'x' => 1,
				'y' => 2
			]
		];

		$this->assertEquals($expected, $res);
	}

	/**
	 * Test deep identical capabilities
	 */
	public function testDeepIdenticalCapabilities(): void {
		$this->manager->registerCapability(function () {
			return new DeepCapability();
		});
		$this->manager->registerCapability(function () {
			return new DeepCapability();
		});

		$res = $this->manager->getCapabilities();
		$expected = [
			'foo' => [
				'bar' => [
					'baz' => true
				]
			]
		];

		$this->assertEquals($expected, $res);
	}

	public function testInvalidCapability(): void {
		$this->manager->registerCapability(function (): void {
			throw new QueryException();
		});

		$this->logger->expects($this->once())
			->method('error');

		$res = $this->manager->getCapabilities();

		$this->assertEquals([], $res);
	}
}

class SimpleCapability implements ICapability {
	public function getCapabilities() {
		return [
			'foo' => 1
		];
	}
}

class SimpleCapability2 implements ICapability {
	public function getCapabilities() {
		return [
			'bar' => ['x' => 1]
		];
	}
}

class SimpleCapability3 implements ICapability {
	public function getCapabilities() {
		return [
			'bar' => ['y' => 2]
		];
	}
}

class PublicSimpleCapability1 implements IPublicCapability {
	public function getCapabilities() {
		return [
			'foo' => 1
		];
	}
}

class NoCapability {
	public function getCapabilities() {
		return [
			'baz' => 'z'
		];
	}
}

class DeepCapability implements ICapability {
	public function getCapabilities() {
		return [
			'foo' => [
				'bar' => [
					'baz' => true
				]
			]
		];
	}
}
