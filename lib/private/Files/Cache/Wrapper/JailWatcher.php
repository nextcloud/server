<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Robin Appelman <robin@icewind.nl>
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

namespace OC\Files\Cache\Wrapper;

use OC\Files\Cache\Watcher;

class JailWatcher extends Watcher {
	private string $root;
	private Watcher $watcher;

	public function __construct(Watcher $watcher, string $root) {
		$this->watcher = $watcher;
		$this->root = $root;
	}

	protected function getRoot(): string {
		return $this->root;
	}

	protected function getSourcePath($path): string {
		if ($path === '') {
			return $this->getRoot();
		} else {
			return $this->getRoot() . '/' . ltrim($path, '/');
		}
	}

	public function setPolicy($policy) {
		$this->watcher->setPolicy($policy);
	}

	public function getPolicy() {
		return $this->watcher->getPolicy();
	}


	public function checkUpdate($path, $cachedEntry = null) {
		return $this->watcher->checkUpdate($this->getSourcePath($path), $cachedEntry);
	}

	public function update($path, $cachedData) {
		$this->watcher->update($this->getSourcePath($path), $cachedData);
	}

	public function needsUpdate($path, $cachedData) {
		return $this->watcher->needsUpdate($this->getSourcePath($path), $cachedData);
	}

	public function cleanFolder($path) {
		$this->watcher->cleanFolder($this->getSourcePath($path));
	}

}
