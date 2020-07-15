<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
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
use OCP\AppFramework\QueryException;
use OCP\Dashboard\IManager;
use OCP\Dashboard\IPanel;
use OCP\ILogger;
use OCP\IServerContainer;
use Throwable;

class Manager implements IManager {

	/** @var array */
	private $lazyPanels = [];

	/** @var IPanel[] */
	private $panels = [];

	/** @var IServerContainer */
	private $serverContainer;

	public function __construct(IServerContainer $serverContainer) {
		$this->serverContainer = $serverContainer;
	}

	private function registerPanel(IPanel $panel): void {
		if (array_key_exists($panel->getId(), $this->panels)) {
			throw new InvalidArgumentException('Dashboard panel with this id has already been registered');
		}

		$this->panels[$panel->getId()] = $panel;
	}

	public function lazyRegisterPanel(string $panelClass): void {
		$this->lazyPanels[] = $panelClass;
	}

	public function loadLazyPanels(): void {
		$classes = $this->lazyPanels;
		foreach ($classes as $class) {
			try {
				/** @var IPanel $panel */
				$panel = $this->serverContainer->query($class);
			} catch (QueryException $e) {
				/*
				 * There is a circular dependency between the logger and the registry, so
				 * we can not inject it. Thus the static call.
				 */
				\OC::$server->getLogger()->logException($e, [
					'message' => 'Could not load lazy dashbaord panel: ' . $e->getMessage(),
					'level' => ILogger::FATAL,
				]);
			}
			/**
			 * Try to register the loaded reporter. Theoretically it could be of a wrong
			 * type, so we might get a TypeError here that we should catch.
			 */
			try {
				$this->registerPanel($panel);
			} catch (Throwable $e) {
				/*
				 * There is a circular dependency between the logger and the registry, so
				 * we can not inject it. Thus the static call.
				 */
				\OC::$server->getLogger()->logException($e, [
					'message' => 'Could not register lazy dashboard panel: ' . $e->getMessage(),
					'level' => ILogger::FATAL,
				]);
			}

			try {
				$panel->load();
			} catch (Throwable $e) {
				\OC::$server->getLogger()->logException($e, [
					'message' => 'Error during dashboard panel loading: ' . $e->getMessage(),
					'level' => ILogger::FATAL,
				]);
			}
		}
		$this->lazyPanels = [];
	}

	public function getPanels(): array {
		$this->loadLazyPanels();
		return $this->panels;
	}
}
