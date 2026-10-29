<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dashboard\Tests;

use OCA\Dashboard\Controller\TeamDashboardApiController;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Dashboard\IManager;
use OCP\Dashboard\ITeamWidget;
use OCP\Dashboard\Model\WidgetItems;
use OCP\IRequest;
use OCP\Teams\ITeamManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

class TeamDashboardApiControllerTest extends TestCase {
	private function controller(bool $member, array $widgets = []): TeamDashboardApiController {
		$manager = $this->createMock(IManager::class);
		$manager->expects($member ? self::once() : self::never())->method('getTeamWidgets')->willReturn($widgets);
		$teams = $this->createMock(ITeamManager::class);
		$teams->expects(self::once())->method('isMemberOfTeam')->with('team-a', 'alice')->willReturn($member);
		return new TeamDashboardApiController('dashboard', $this->createMock(IRequest::class), $manager, $teams, 'alice', new NullLogger());
	}

	public function testUnauthorizedItemsDoNotDiscoverProviders(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->controller(false)->getWidgetItems('team-a');
	}

	public function testUnauthorizedMetadataDoesNotDiscoverProviders(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->controller(false)->getWidgets('team-a');
	}

	public function testTeamContextAndFailureIsolation(): void {
		$healthy = $this->createMock(ITeamWidget::class);
		$healthy->expects(self::once())->method('getTeamItems')->with('team-a', 'alice', null, 7)->willReturn(new WidgetItems([], 'No pages'));
		$unavailable = $this->createMock(ITeamWidget::class);
		$unavailable->method('getTeamItems')->willReturn(null);
		$failed = $this->createMock(ITeamWidget::class);
		$failed->method('getTeamItems')->willThrowException(new RuntimeException('private detail'));
		$result = $this->controller(true, ['healthy' => $healthy, 'unavailable' => $unavailable, 'failed' => $failed])->getWidgetItems('team-a')->getData();
		self::assertSame('ok', $result['healthy']['status']);
		self::assertSame('No pages', $result['healthy']['data']['emptyContentMessage']);
		self::assertSame(['status' => 'unavailable'], $result['unavailable']);
		self::assertSame(['status' => 'error'], $result['failed']);
	}

	public function testInvalidLimit(): void {
		$this->expectException(OCSBadRequestException::class);
		$this->controller(true)->getWidgetItems('team-a', limit: 31);
	}

	public function testMetadataDoesNotFetchItemsOrExposePersonalLinks(): void {
		$widget = $this->createMock(ITeamWidget::class);
		$widget->method('getId')->willReturn('pages');
		$widget->method('getTitle')->willReturn('Recent pages');
		$widget->expects(self::never())->method('getTeamItems');
		$widget->expects(self::never())->method('getUrl');
		$metadata = $this->controller(true, ['pages' => $widget])->getWidgets('team-a')->getData();
		self::assertSame('Recent pages', $metadata['pages']['title']);
		self::assertArrayNotHasKey('widget_url', $metadata['pages']);
		self::assertArrayNotHasKey('buttons', $metadata['pages']);
	}

	public function testInvalidCursorDoesNotInvokeWidget(): void {
		$widget = $this->createMock(ITeamWidget::class);
		$widget->expects(self::never())->method('getTeamItems');
		$this->expectException(OCSBadRequestException::class);
		$this->controller(true, ['pages' => $widget])->getWidgetItems('team-a', sinceIds: ['pages' => ['invalid']]);
	}

	public function testUnknownWidget(): void {
		$this->expectException(OCSBadRequestException::class);
		$this->controller(true)->getWidgetItems('team-a', ['personal']);
	}

	public function testWidgetSelectionAndCursor(): void {
		$widget = $this->createMock(ITeamWidget::class);
		$widget->expects(self::once())->method('getTeamItems')->with('team-a', 'alice', 'cursor-a', 3)->willReturn(new WidgetItems());
		$other = $this->createMock(ITeamWidget::class);
		$other->expects(self::never())->method('getTeamItems');
		$result = $this->controller(true, ['pages' => $widget, 'other' => $other])->getWidgetItems('team-a', ['pages'], 3, ['pages' => 'cursor-a'])->getData();
		self::assertSame(['pages'], array_keys($result));
	}
}
