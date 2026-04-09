<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Dashboard;

use InvalidArgumentException;
use OCP\App\IAppManager;
use OCP\Dashboard\IConditionalWidget;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IWidget;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Manager implements IManager {
	/** @var array */
	private $lazyWidgets = [];

	/** @var array<string, IWidget> */
	private array $widgets = [];

	private ?IAppManager $appManager = null;

	public function __construct(
		private ContainerInterface $serverContainer,
		private LoggerInterface $logger,
	) {
	}

	private function registerWidget(IWidget $widget): void {
		if (array_key_exists($widget->getId(), $this->widgets)) {
			throw new InvalidArgumentException('Dashboard widget with this id has already been registered');
		}

		if (!preg_match('/^[a-z][a-z0-9\-_]*$/', $widget->getId())) {
			$this->logger->debug('Deprecated dashboard widget ID provided: "' . $widget->getId() . '" [ ' . get_class($widget) . ' ]. Please use a-z, 0-9, - and _ only, starting with a-z');
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
				Server::get(LoggerInterface::class)->critical(
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
				Server::get(LoggerInterface::class)->critical(
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
					Server::get(LoggerInterface::class)->info(
						'Dashboard widget {widget} took {duration} seconds to load.',
						[
							'widget' => $widget->getId(),
							'duration' => round($duration, 2),
						]
					);
				}
			} catch (Throwable $e) {
				Server::get(LoggerInterface::class)->critical(
					'Error during dashboard widget loading: ' . $service['class'],
					['exception' => $e]
				);
				continue;
			}
		}
		$this->lazyWidgets = [];
	}

	/**
	 * @return array<string, IWidget>
	 */
	public function getWidgets(): array {
		$this->loadLazyPanels();
		return $this->widgets;
	}
}
