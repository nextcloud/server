<?php
/**
 * @copyright Copyright (c) 2021, Jonas Meurer <jonas@freesources.org>
 *
 * @author Jonas Meurer <jonas@freesources.org>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC;

use Psr\Log\LoggerInterface;

/**
 * Sort scripts topologically by their dependencies
 * Implementation based on https://github.com/marcj/topsort.php
 */
class AppScriptSort {
	/** @var LoggerInterface */
	private $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	/**
	 * Recursive topological sorting
	 *
	 * @param AppScriptDependency $app
	 * @param array $parents
	 * @param array $scriptDeps
	 * @param array $sortedScriptDeps
	 */
	private function topSortVisit(
			AppScriptDependency $app,
			array &$parents,
			array &$scriptDeps,
			array &$sortedScriptDeps): void {
		// Detect and log circular dependencies
		if (isset($parents[$app->getId()])) {
			$this->logger->error('Circular dependency in app scripts at app ' . $app->getId());
		}

		// If app has not been visited
		if (!$app->isVisited()) {
			$parents[$app->getId()] = true;
			$app->setVisited(true);

			foreach ($app->getDeps() as $dep) {
				if ($app->getId() === $dep) {
					// Ignore dependency on itself
					continue;
				}

				if (isset($scriptDeps[$dep])) {
					$newParents = $parents;
					$this->topSortVisit($scriptDeps[$dep], $newParents, $scriptDeps, $sortedScriptDeps);
				}
			}

			$sortedScriptDeps[] = $app->getId();
		}
	}

	/**
	 * @return array scripts sorted by dependencies
	 */
	public function sort(array $scripts, array $scriptDeps): array {
		// Sort scriptDeps into sortedScriptDeps
		$sortedScriptDeps = [];
		foreach ($scriptDeps as $app) {
			$parents = [];
			$this->topSortVisit($app, $parents, $scriptDeps, $sortedScriptDeps);
		}

		// Sort scripts into sortedScripts based on sortedScriptDeps order
		$sortedScripts = [];
		foreach ($sortedScriptDeps as $app) {
			$sortedScripts[$app] = $scripts[$app] ?? [];
		}

		// Add remaining scripts
		foreach (array_keys($scripts) as $app) {
			if (!isset($sortedScripts[$app])) {
				$sortedScripts[$app] = $scripts[$app];
			}
		}

		return $sortedScripts;
	}
}
