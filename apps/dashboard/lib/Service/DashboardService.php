<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Dashboard\Service;

use JsonException;
use OCP\IConfig;

class DashboardService {
	public function __construct(
		private IConfig $config,
		private ?string $userId,
	) {

	}

	/**
	 * @return list<string>
	 */
	public function getLayout(): array {
		$systemDefault = $this->config->getAppValue('dashboard', 'layout', 'recommendations,spreed,mail,calendar');
		return array_values(array_filter(explode(',', $this->config->getUserValue($this->userId, 'dashboard', 'layout', $systemDefault)), fn (string $value) => $value !== ''));
	}

	/**
	 * @return list<string>
	 */
	public function getStatuses() {
		$configStatuses = $this->config->getUserValue($this->userId, 'dashboard', 'statuses', '');
		try {
			// Parse the old format
			/** @var array<string, bool> $statuses */
			$statuses = json_decode($configStatuses, true, 512, JSON_THROW_ON_ERROR);
			// We avoid getting an empty array as it will not produce an object in UI's JS
			return array_keys(array_filter($statuses, static fn (bool $value) => $value));
		} catch (JsonException $e) {
			return array_values(array_filter(explode(',', $configStatuses), fn (string $value) => $value !== ''));
		}
	}
}
