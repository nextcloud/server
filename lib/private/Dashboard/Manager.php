<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
namespace OC\Dashboard;

use InvalidArgumentException;
use OCP\App\IAppManager;
use OCP\Dashboard\IConditionalWidget;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IWidget;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Throwable;
use Psr\Log\LoggerInterface;

class Manager implements IManager {
	/** @var array */
	private $lazyWidgets = [];

	/** @var IWidget[] */
	private $widgets = [];

	private ContainerInterface $serverContainer;
	private ?IAppManager $appManager = null;

	public function __construct(ContainerInterface $serverContainer) {
		$this->serverContainer = $serverContainer;
	}

	private function registerWidget(IWidget $widget): void {
		if (array_key_exists($widget->getId(), $this->widgets)) {
			throw new InvalidArgumentException('Dashboard widget with this id has already been registered');
		}

		$this->widgets[$widget->getId()] = $widget;
	}

	public function lazyRegisterWidget(string $widgetClass, string $appId): void {
		$this->lazyWidgets[] = ['class' => $widgetClass, 'appId' => $appId];
	}

	public function loadLazyPanels(): void {
		if ($this->appManager === null) {
			$this->appManager = $this->serverContainer->get(IAppManager::class);
		}
		$services = $this->lazyWidgets;
		foreach ($services as $service) {
			/** @psalm-suppress InvalidCatch */
			try {
				if (!$this->appManager->isEnabledForUser($service['appId'])) {
					// all apps are registered, but some may not be enabled for the user
					continue;
				}
				/** @var IWidget $widget */
				$widget = $this->serverContainer->get($service['class']);
			} catch (ContainerExceptionInterface $e) {
				/*
				 * There is a circular dependency between the logger and the registry, so
				 * we can not inject it. Thus the static call.
				 */
				\OC::$server->get(LoggerInterface::class)->critical(
					'Could not load lazy dashboard widget: ' . $service['class'],
					['exception' => $e]
				);
				continue;
			}
			/**
			 * Try to register the loaded reporter. Theoretically it could be of a wrong
			 * type, so we might get a TypeError here that we should catch.
			 */
			try {
				if ($widget instanceof IConditionalWidget && !$widget->isEnabled()) {
					continue;
				}

				$this->registerWidget($widget);
			} catch (Throwable $e) {
				/*
				 * There is a circular dependency between the logger and the registry, so
				 * we can not inject it. Thus the static call.
				 */
				\OC::$server->get(LoggerInterface::class)->critical(
					'Could not register lazy dashboard widget: ' . $service['class'],
					['exception' => $e]
				);
				continue;
			}

			try {
				$startTime = microtime(true);
				$widget->load();
				$endTime = microtime(true);
				$duration = $endTime - $startTime;
				if ($duration > 1) {
					\OC::$server->get(LoggerInterface::class)->error(
						'Dashboard widget {widget} took {duration} seconds to load.',
						[
							'widget' => $widget->getId(),
							'duration' => round($duration, 2),
						]
					);
				}
			} catch (Throwable $e) {
				\OC::$server->get(LoggerInterface::class)->critical(
					'Error during dashboard widget loading: ' . $service['class'],
					['exception' => $e]
				);
				continue;
			}
		}
		$this->lazyWidgets = [];
	}

	public function getWidgets(): array {
		$this->loadLazyPanels();
		return $this->widgets;
	}
}
