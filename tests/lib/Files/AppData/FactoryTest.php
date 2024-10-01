<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\AppData;

use OC\Files\AppData\Factory;
use OC\SystemConfig;
use OCP\Files\IRootFolder;

class FactoryTest extends \Test\TestCase {
	/** @var IRootFolder|\PHPUnit\Framework\MockObject\MockObject */
	private $rootFolder;

	/** @var SystemConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $systemConfig;

	/** @var Factory */
	private $factory;

	protected function setUp(): void {
		parent::setUp();

		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->systemConfig = $this->createMock(SystemConfig::class);
		$this->factory = new Factory($this->rootFolder, $this->systemConfig);
	}

	public function testGet(): void {
		$this->rootFolder->expects($this->never())
			->method($this->anything());
		$this->systemConfig->expects($this->never())
			->method($this->anything());

		$this->factory->get('foo');
	}
}
