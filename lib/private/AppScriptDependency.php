<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC;

class AppScriptDependency {
	/** @var string */
	private $id;

	/** @var array */
	private $deps;

	/** @var bool */
	private $visited;

	/**
	 * @param string $id
	 * @param array $deps
	 * @param bool $visited
	 */
	public function __construct(string $id, array $deps = [], bool $visited = false) {
		$this->setId($id);
		$this->setDeps($deps);
		$this->setVisited($visited);
	}

	/**
	 * @return string
	 */
	public function getId(): string {
		return $this->id;
	}

	/**
	 * @param string $id
	 */
	public function setId(string $id): void {
		$this->id = $id;
	}

	/**
	 * @return array
	 */
	public function getDeps(): array {
		return $this->deps;
	}

	/**
	 * @param array $deps
	 */
	public function setDeps(array $deps): void {
		$this->deps = $deps;
	}

	/**
	 * @param string $dep
	 */
	public function addDep(string $dep): void {
		if (!in_array($dep, $this->deps, true)) {
			$this->deps[] = $dep;
		}
	}

	/**
	 * @return bool
	 */
	public function isVisited(): bool {
		return $this->visited;
	}

	/**
	 * @param bool $visited
	 */
	public function setVisited(bool $visited): void {
		$this->visited = $visited;
	}
}
