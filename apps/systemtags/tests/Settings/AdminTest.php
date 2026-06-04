<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\SystemTags\Tests\Settings;

use OCA\SystemTags\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var Admin */
	private $admin;
	/** @var IAppConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $appConfig;
	/** @var IInitialState|\PHPUnit\Framework\MockObject\MockObject */
	private $initialState;

	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->initialState = $this->createMock(IInitialState::class);

		$this->admin = new Admin(
			$this->appConfig,
			$this->initialState
		);
	}

	public function testGetForm(): void {
		$this->appConfig->expects($this->once())
			->method('getValueBool')
			->with('systemtags', 'restrict_creation_to_admin', false)
			->willReturn(false);

		$this->initialState->expects($this->once())
			->method('provideInitialState')
			->with('restrictSystemTagsCreationToAdmin', false);

		$expected = new TemplateResponse('systemtags', 'admin', [], '');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetFormWithRestrictedCreation(): void {
		$this->appConfig->expects($this->once())
			->method('getValueBool')
			->with('systemtags', 'restrict_creation_to_admin', false)
			->willReturn(true);

		$this->initialState->expects($this->once())
			->method('provideInitialState')
			->with('restrictSystemTagsCreationToAdmin', true);

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
