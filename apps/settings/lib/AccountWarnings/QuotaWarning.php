<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
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
namespace OCA\Settings\AccountWarnings;

use OCP\Settings\IAccountWarning;
use OCP\IL10N;

class QuotaWarning implements IAccountWarning {
	public const THRESHOLDS = [
		98 => IAccountWarning::SEVERITY_ERROR,
		90 => IAccountWarning::SEVERITY_WARNING,
		80 => IAccountWarning::SEVERITY_INFO,
	];

	public function __construct(
		private IL10N $l10n,
		private array $userIds,
		private int $threshold,
	) {
	}

	public function getText(): string {
		return $this->l10n->n(
			'%n account is using more than %d%% of their quota: %s',
			'%n accounts are using more than %d%% of their quota: %s',
			count($this->userIds),
			[$this->threshold, implode(',', $this->userIds)]
		);
	}

	public function getSeverity(): string {
		return self::THRESHOLDS[$this->threshold];
	}
}
