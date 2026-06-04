<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Storage;

trait ConfigurableStorageTrait {
	protected ?array $config = null;

	protected function loadConfig(string $path): bool {
		$this->config = null;
		if (file_exists($path)) {
			$this->config = include($path);
		}
		if (!$this->shouldRunConfig($this->config)) {
			$this->markTestSkipped(__CLASS__ . ' Backend not configured');
			return false;
		}
		return true;
	}

	protected function shouldRunConfig(mixed $config): bool {
		return is_array($config) && ($config['run'] ?? false);
	}
}
