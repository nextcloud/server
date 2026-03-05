<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\Dashboard;

use OCA\UserStatus\Dashboard\UserStatusWidget;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Services\IInitialState;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserStatusWidgetTest extends TestCase {
	private IL10N&MockObject $l10n;
	private IDateTimeFormatter&MockObject $dateTimeFormatter;
	private IURLGenerator&MockObject $urlGenerator;
	private IInitialState&MockObject $initialState;
	private IUserManager&MockObject $userManager;
	private IUserSession&MockObject $userSession;
	private StatusService&MockObject $service;
	private UserStatusWidget $widget;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->dateTimeFormatter = $this->createMock(IDateTimeFormatter::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->service = $this->createMock(StatusService::class);

		$this->widget = new UserStatusWidget($this->l10n, $this->dateTimeFormatter, $this->urlGenerator, $this->initialState, $this->userManager, $this->userSession, $this->service);
	}

	public function testGetId(): void {
		$this->assertEquals('user_status', $this->widget->getId());
	}

	public function testGetTitle(): void {
		$this->l10n->expects($this->exactly(1))
			->method('t')
			->willReturnArgument(0);

		$this->assertEquals('Recent statuses', $this->widget->getTitle());
	}

	public function testGetOrder(): void {
		$this->assertEquals(5, $this->widget->getOrder());
	}

	public function testGetIconClass(): void {
		$this->assertEquals('icon-user-status-dark', $this->widget->getIconClass());
	}

	public function testGetUrl(): void {
		$this->assertNull($this->widget->getUrl());
	}
}
