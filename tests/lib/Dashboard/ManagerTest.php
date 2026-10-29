<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Dashboard;

use OC\Dashboard\Manager;
use OCP\App\IAppManager;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IConditionalWidget;
use OCP\Dashboard\ITeamWidget;
use OCP\Dashboard\IWidget;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

interface DualWidget extends ITeamWidget, IAPIWidgetV2 {
}

interface ConditionalTeamWidget extends ITeamWidget, IConditionalWidget {
}

class ManagerTest extends TestCase {
	private function manager(array $widgets, bool $enabled = true): Manager {
		$apps = $this->createMock(IAppManager::class);
		$apps->method('isEnabledForUser')->willReturn($enabled);
		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willReturnCallback(static fn (string $id) => $id === IAppManager::class ? $apps : $widgets[$id]);
		$manager = new Manager($container, new NullLogger());
		foreach ($widgets as $id => $widget) {
			$widget->method('getId')->willReturn($id);
			$manager->lazyRegisterWidget($id, 'example');
		}
		return $manager;
	}

	public function testTeamDiscoveryLoadsWidgetsOnceAndDoesNotExposePersonalWidgets(): void {
		$team = $this->createMock(ITeamWidget::class);
		$team->expects(self::once())->method('load');
		$personal = $this->createMock(IWidget::class);
		$personal->expects(self::once())->method('load');
		$manager = $this->manager(['team' => $team, 'personal' => $personal]);
		self::assertSame(['team' => $team], $manager->getTeamWidgets());
		self::assertSame(['personal' => $personal], $manager->getWidgets());
		self::assertSame(['personal' => $personal], $manager->getWidgets());
		self::assertSame(['team' => $team], $manager->getTeamWidgets());
	}

	public function testDualWidgetAppearsInBothContextsButLoadsOnce(): void {
		$widget = $this->createMock(DualWidget::class);
		$widget->expects(self::once())->method('load');
		$manager = $this->manager(['dual' => $widget]);
		self::assertSame(['dual' => $widget], $manager->getWidgets());
		self::assertSame(['dual' => $widget], $manager->getTeamWidgets());
		$manager->getWidgets();
	}

	public function testDisabledAppDoesNotExposeWidgets(): void {
		$widget = $this->createMock(ITeamWidget::class);
		$widget->expects(self::never())->method('load');
		self::assertSame([], $this->manager(['team' => $widget], false)->getTeamWidgets());
	}

	public function testConditionalWidgetIsRespected(): void {
		$widget = $this->createMock(ConditionalTeamWidget::class);
		$widget->method('isEnabled')->willReturn(false);
		self::assertSame([], $this->manager(['team' => $widget])->getTeamWidgets());
	}
}
