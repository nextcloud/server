<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
use Test\TestCase;

class UserStatusWidgetTest extends TestCase {

	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10n;

	/** @var IDateTimeFormatter|\PHPUnit\Framework\MockObject\MockObject */
	private $dateTimeFormatter;

	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;

	/** @var IInitialState|\PHPUnit\Framework\MockObject\MockObject */
	private $initialState;

	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;

	/** @var StatusService|\PHPUnit\Framework\MockObject\MockObject */
	private $service;

	/** @var UserStatusWidget */
	private $widget;

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
