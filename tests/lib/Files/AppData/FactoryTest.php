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
	/** @var Factory */
	private $factory;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->factory = $this->createInstanceWithMocks(Factory::class);
	}

	public function testGet(): void {
		$this->mocks[IRootFolder::class]->expects($this->never())
			->method($this->anything());
		$this->mocks[SystemConfig::class]->expects($this->never())
			->method($this->anything());

		$this->factory->get('foo');
	}
}
