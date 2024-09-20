<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\IntegrityCheck\Helpers;

use OC\IntegrityCheck\Helpers\EnvironmentHelper;
use Test\TestCase;

class EnvironmentHelperTest extends TestCase {
	/** @var EnvironmentHelper */
	private $environmentHelper;

	protected function setUp(): void {
		$this->environmentHelper = new EnvironmentHelper();
		parent::setUp();
	}

	public function testGetServerRoot(): void {
		$this->assertSame(\OC::$SERVERROOT, $this->environmentHelper->getServerRoot());
	}
}
