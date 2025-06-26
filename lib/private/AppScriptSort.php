<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
