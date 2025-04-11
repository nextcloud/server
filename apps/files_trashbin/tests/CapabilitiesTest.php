<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Tests;

use OCA\Files_Trashbin\Capabilities;
use Test\TestCase;

class CapabilitiesTest extends TestCase {

	/** @var Capabilities */
	private $capabilities;

	protected function setUp(): void {
		parent::setUp();
		$this->capabilities = new Capabilities();
	}

	public function testGetCapabilities(): void {
		$capabilities = [
			'files' => [
				'undelete' => true,
				'delete_from_trash' => true,
			]
		];

		$this->assertSame($capabilities, $this->capabilities->getCapabilities());
	}
}
