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
