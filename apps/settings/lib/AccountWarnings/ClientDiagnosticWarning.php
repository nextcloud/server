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

use OCA\Settings\Db\ClientDiagnostic;
use OCP\Settings\IAccountWarning;
use OCP\IL10N;

class ClientDiagnosticWarning implements IAccountWarning {
	/**
	 * @param string $type one of ClientDiagnostic::TYPE_* constants
	 */
	public function __construct(
		private IL10N $l10n,
		private string $type,
		private int $count,
		private string $uid,
		private string $clientName,
		private int $oldest,
	) {
	}

	public function getText(): string {
		$oldest = new \DateTime();
		$oldest->setTimestamp($this->oldest);
		// TODO check which format to use
		$formattedOldest = $oldest->format('Y-m-d H:i:s');
		return match ($this->type) {
			ClientDiagnostic::TYPE_CONFLICT =>
				$this->l10n->n(
					'Account "%s" had %n conflict on client %s on %s',
					'Account "%s" had %n conflicts on client %s, oldest one on %s',
					$this->count,
					[$this->uid, $this->clientName, $formattedOldest]
				),
			ClientDiagnostic::TYPE_FAILED_UPLOAD =>
				$this->l10n->n(
					'Account %s had %n failed upload on client %s on %s',
					'Account %s had %n failed uploads on client %s, oldest one on %s',
					$this->count,
					[$this->uid, $this->clientName, $formattedOldest]
				),
			default => 'Unknown problem',
		};
	}

	public function getSeverity(): string {
		return match ($this->type) {
			ClientDiagnostic::TYPE_CONFLICT => IAccountWarning::SEVERITY_WARNING,
			ClientDiagnostic::TYPE_FAILED_UPLOAD => IAccountWarning::SEVERITY_WARNING,
			default => IAccountWarning::SEVERITY_ERROR,
		};
	}
}
