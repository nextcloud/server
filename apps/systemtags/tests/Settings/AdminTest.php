<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\Tests\Settings;

use OCA\SystemTags\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var Admin */
	private $admin;

	protected function setUp(): void {
		parent::setUp();

		$this->admin = new Admin();
	}

	public function testGetForm(): void {
		$expected = new TemplateResponse('systemtags', 'admin', [], '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('server', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(70, $this->admin->getPriority());
	}
}
