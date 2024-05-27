<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
