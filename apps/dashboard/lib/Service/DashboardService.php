<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024, Kate Döen <kate.doeen@nextcloud.com>
 *
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OCA\Dashboard\Service;

use JsonException;
use OCP\IConfig;

class DashboardService {
	public function __construct(
		private IConfig $config,
		private String $userId,
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
