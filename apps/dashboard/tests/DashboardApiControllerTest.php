<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dashboard\Tests;

use OCA\Dashboard\Controller\DashboardApiController;
use OCA\Dashboard\Service\DashboardService;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Config\IUserConfig;
use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\IReloadableWidget;
use OCP\Dashboard\ITeamWidget;
use OCP\Dashboard\IWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetItems;
use OCP\Dashboard\Model\WidgetOptions;
use OCP\IRequest;
use OCP\Teams\ITeamManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

interface TestTeamWidget extends ITeamWidget, IAPIWidget, IButtonWidget, IIconWidget, IOptionWidget, IReloadableWidget {
}

class DashboardApiControllerTest extends TestCase {
	private IManager&MockObject $dashboardManager;
	private IAppConfig&MockObject $appConfig;
	private IUserConfig&MockObject $userConfig;
	private ITeamManager&MockObject $teamManager;
	private LoggerInterface&MockObject $logger;
	private DashboardApiController $controller;

	protected function setUp(): void {
		parent::setUp();
		$this->dashboardManager = $this->createMock(IManager::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->teamManager = $this->createMock(ITeamManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->controller = $this->createController('alice');
	}

	private function createController(?string $userId): DashboardApiController {
		return new DashboardApiController(
			'dashboard',
			$this->createMock(IRequest::class),
			$this->dashboardManager,
			$this->appConfig,
			$this->userConfig,
			$userId,
			$this->createMock(DashboardService::class),
			$this->teamManager,
			$this->logger,
		);
	}

	public function testPersonalWidgetMetadata(): void {
		$this->teamManager->expects($this->never())->method('isMemberOfTeam');
		$widget = $this->createMock(TestTeamWidget::class);
		$widget->method('getId')->willReturn('widget');
		$widget->method('getTitle')->willReturn('Widget');
		$widget->method('getOrder')->willReturn(10);
		$widget->method('getIconClass')->willReturn('icon-widget');
		$widget->method('getIconUrl')->willReturn('/icon.svg');
		$widget->method('getUrl')->willReturn('/personal');
		$widget->method('getWidgetOptions')->willReturn(new WidgetOptions(true));
		$widget->method('getReloadInterval')->willReturn(60);
		$widget->expects($this->once())->method('getWidgetButtons')->with('alice')
			->willReturn([new WidgetButton(WidgetButton::TYPE_MORE, '/more', 'More')]);
		$widget->expects($this->never())->method('isEnabledForTeam');
		$widget->expects($this->never())->method('getTeamUrl');
		$plain = $this->createMock(IWidget::class);
		$this->dashboardManager->method('getWidgets')->willReturn(['widget' => $widget, 'plain' => $plain]);

		$data = $this->controller->getWidgets()->getData();

		$this->assertSame([
			'id' => 'widget',
			'title' => 'Widget',
			'order' => 10,
			'icon_class' => 'icon-widget',
			'icon_url' => '/icon.svg',
			'widget_url' => '/personal',
			'item_icons_round' => true,
			'item_api_versions' => [1, 2],
			'reload_interval' => 60,
			'buttons' => [['type' => WidgetButton::TYPE_MORE, 'text' => 'More', 'link' => '/more']],
		], $data['widget']);
		$this->assertSame(['widget', 'plain'], array_keys($data));
		$this->assertSame('', $data['plain']['icon_url']);
		$this->assertSame(0, $data['plain']['reload_interval']);
		$this->assertSame([], $data['plain']['item_api_versions']);
		$this->assertSame(WidgetOptions::getDefault()->withRoundItemIcons(), $data['plain']['item_icons_round']);
		$this->assertArrayNotHasKey('buttons', $data['plain']);
	}

	public static function personalItemsProvider(): array {
		return [
			'saved layout' => [[]],
			'explicit widgets' => [['widget']],
		];
	}

	#[DataProvider('personalItemsProvider')]
	public function testPersonalWidgetItemsV2(array $widgets): void {
		$this->teamManager->expects($this->never())->method('isMemberOfTeam');
		if ($widgets === []) {
			$this->appConfig->expects($this->once())->method('getAppValueString')
				->with('layout', 'recommendations,spreed,mail,calendar')->willReturn('default');
			$this->userConfig->expects($this->once())->method('getValueString')
				->with('alice', 'dashboard', 'layout', 'default')->willReturn('widget');
		} else {
			$this->userConfig->expects($this->never())->method('getValueString');
		}
		$items = new WidgetItems([new WidgetItem('Title', 'Subtitle', '/link', '/icon.svg', 'cursor')], 'Empty', 'Half empty');
		$widget = $this->createMock(ITeamWidget::class);
		$widget->method('getId')->willReturn('widget');
		$widget->expects($this->once())->method('getItemsV2')->with('alice', 'cursor', 5)->willReturn($items);
		$widget->expects($this->never())->method('getTeamItems');
		$widget->expects($this->never())->method('isEnabledForTeam');
		$unselected = $this->createMock(IAPIWidgetV2::class);
		$unselected->method('getId')->willReturn('unselected');
		$unselected->expects($this->never())->method('getItemsV2');
		$this->dashboardManager->method('getWidgets')->willReturn(['widget' => $widget, 'unselected' => $unselected]);

		$this->assertSame(['widget' => $items->jsonSerialize()], $this->controller->getWidgetItemsV2(['widget' => 'cursor'], 5, $widgets)->getData());
	}

	public static function rejectedTeamProvider(): array {
		$cases = [];
		foreach (['getWidgets', 'getWidgetItemsV2'] as $method) {
			$cases[$method . ' empty team'] = [$method, '', 'alice', false, OCSBadRequestException::class];
			$cases[$method . ' non-member'] = [$method, 'team', 'alice', false, OCSNotFoundException::class];
			$cases[$method . ' no session user'] = [$method, 'team', null, false, OCSNotFoundException::class];
		}
		return $cases;
	}

	#[DataProvider('rejectedTeamProvider')]
	public function testRejectsTeamBeforeLoadingWidgets(string $method, string $teamId, ?string $userId, bool $isMember, string $exceptionClass): void {
		$this->dashboardManager->expects($this->never())->method('getWidgets');
		$this->userConfig->expects($this->never())->method('getValueString');
		if ($teamId !== '' && $userId !== null) {
			$this->teamManager->expects($this->once())->method('isMemberOfTeam')->with($teamId, $userId)->willReturn($isMember);
		} else {
			$this->teamManager->expects($this->never())->method('isMemberOfTeam');
		}
		$this->expectException($exceptionClass);
		$this->expectExceptionMessage($teamId === '' ? 'Empty team ID' : 'Team not found');
		$this->createController($userId)->$method(teamId: $teamId);
	}

	public function testTeamWidgetMetadataFiltersAndUsesTeamUrls(): void {
		$this->teamManager->expects($this->once())->method('isMemberOfTeam')->with('team', 'alice')->willReturn(true);
		$this->userConfig->expects($this->never())->method('getValueString');
		$team = $this->createMock(ITeamWidget::class);
		$team->method('getId')->willReturn('team-widget');
		$team->expects($this->once())->method('isEnabledForTeam')->with('team', 'alice')->willReturn(true);
		$team->expects($this->once())->method('getTeamUrl')->with('team', 'alice')->willReturn('/team');
		$team->expects($this->never())->method('getUrl');
		$fallback = $this->createMock(ITeamWidget::class);
		$fallback->method('getId')->willReturn('fallback');
		$fallback->method('isEnabledForTeam')->willReturn(true);
		$fallback->method('getTeamUrl')->willReturn(null);
		$fallback->method('getUrl')->willReturn('/personal');
		$disabled = $this->createMock(ITeamWidget::class);
		$disabled->method('isEnabledForTeam')->willReturn(false);
		$disabled->expects($this->never())->method('getTeamUrl');
		$this->dashboardManager->method('getWidgets')->willReturn([
			'personal' => $this->createMock(IAPIWidgetV2::class),
			'team-widget' => $team,
			'fallback' => $fallback,
			'disabled' => $disabled,
		]);

		$data = $this->controller->getWidgets('team')->getData();

		$this->assertSame(['team-widget', 'fallback'], array_keys($data));
		$this->assertSame('/team', $data['team-widget']['widget_url']);
		$this->assertSame('/personal', $data['fallback']['widget_url']);
		$this->assertSame([2], $data['team-widget']['item_api_versions']);
	}

	public static function teamItemsProvider(): array {
		return [
			'all applicable with default cursor' => [[], [], 7, null],
			'filter before applicability' => [['widget', 'unknown', 'personal', 'disabled'], ['widget' => 'cursor'], 3, 'cursor'],
		];
	}

	#[DataProvider('teamItemsProvider')]
	public function testTeamWidgetItems(array $widgets, array $sinceIds, int $limit, ?string $cursor): void {
		$this->teamManager->expects($this->once())->method('isMemberOfTeam')->with('team', 'alice')->willReturn(true);
		$this->userConfig->expects($this->never())->method('getValueString');
		$this->appConfig->expects($this->never())->method('getAppValueString');
		$items = new WidgetItems([new WidgetItem('Team page', '', '/page', '/icon.svg')], 'Empty team');
		$widget = $this->createMock(ITeamWidget::class);
		$widget->method('getId')->willReturn('widget');
		$widget->expects($this->once())->method('isEnabledForTeam')->with('team', 'alice')->willReturn(true);
		$widget->expects($this->once())->method('getTeamItems')->with('team', 'alice', $cursor, $limit)->willReturn($items);
		$widget->expects($this->never())->method('getItemsV2');
		$other = $this->createMock(ITeamWidget::class);
		$other->method('getId')->willReturn('other');
		$other->expects($widgets === [] ? $this->once() : $this->never())->method('isEnabledForTeam')->willReturn(true);
		$other->expects($widgets === [] ? $this->once() : $this->never())->method('getTeamItems')
			->with('team', 'alice', null, $limit)->willReturn(new WidgetItems());
		$disabled = $this->createMock(ITeamWidget::class);
		$disabled->expects($this->once())->method('isEnabledForTeam')->willReturn(false);
		$disabled->expects($this->never())->method('getTeamItems');
		$this->dashboardManager->method('getWidgets')->willReturn([
			'widget' => $widget,
			'other' => $other,
			'personal' => $this->createMock(IAPIWidgetV2::class),
			'disabled' => $disabled,
		]);

		$expected = ['widget' => $items->jsonSerialize()];
		if ($widgets === []) {
			$expected['other'] = (new WidgetItems())->jsonSerialize();
		}
		$this->assertSame($expected, $this->controller->getWidgetItemsV2($sinceIds, $limit, $widgets, 'team')->getData());
	}

	public static function teamWidgetFailureProvider(): array {
		$cases = [];
		foreach ([\RuntimeException::class, \Error::class] as $exceptionClass) {
			foreach (['getWidgets', 'getWidgetItemsV2'] as $endpoint) {
				$cases[$exceptionClass . ' applicability ' . $endpoint] = [
					$endpoint, 'isEnabledForTeam', 'Failed to check dashboard widget applicability for team', $exceptionClass,
				];
			}
			$cases[$exceptionClass . ' URL'] = [
				'getWidgets', 'getTeamUrl', 'Failed to get dashboard widget metadata for team', $exceptionClass,
			];
			$cases[$exceptionClass . ' items'] = [
				'getWidgetItemsV2', 'getTeamItems', 'Failed to get dashboard widget items for team', $exceptionClass,
			];
		}
		return $cases;
	}

	#[DataProvider('teamWidgetFailureProvider')]
	public function testBrokenProviderDoesNotHideOtherWidgets(string $endpoint, string $providerMethod, string $message, string $exceptionClass): void {
		$this->teamManager->method('isMemberOfTeam')->willReturn(true);
		$exception = new $exceptionClass('Broken provider');
		$broken = $this->createMock(ITeamWidget::class);
		$broken->expects($this->once())->method($providerMethod)->willThrowException($exception);
		if ($providerMethod === 'isEnabledForTeam') {
			$broken->expects($this->never())->method('getTeamItems');
			$broken->expects($this->never())->method('getTeamUrl');
		} else {
			$broken->method('isEnabledForTeam')->willReturn(true);
		}
		$working = $this->createMock(ITeamWidget::class);
		$working->method('getId')->willReturn('working');
		$working->method('isEnabledForTeam')->willReturn(true);
		$working->method('getTeamItems')->willReturn(new WidgetItems());
		$this->dashboardManager->method('getWidgets')->willReturn(['before' => $working, 'broken' => $broken, 'after' => $working]);
		$this->logger->expects($this->once())->method('warning')->with(
			$message,
			['widget' => 'broken', 'team' => 'team', 'exception' => $exception],
		);

		$this->assertSame(['before', 'after'], array_keys($this->controller->$endpoint(teamId: 'team')->getData()));
	}
}
