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

use OCP\Settings\IAccountWarningsProvider;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\IUser;
use OCP\Files\IRootFolder;

class QuotaWarningsProvider implements IAccountWarningsProvider {
	public function __construct(
		private IL10N $l10n,
		private IUserManager $userManager,
		private IRootFolder $rootFolder,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Quota');
	}

	public function getAccountWarnings(): array {
		$users = [];
		foreach (QuotaWarning::THRESHOLDS as $threshold) {
			$users[$threshold] = 0;
		}
		$this->userManager->callForSeenUsers(function (IUser $user) use (&$users) {
			$userFolder = $this->rootFolder->getUserFolder($user->getUID());
			$size = $userFolder->getSize();
			$quota = \OCP\Util::computerFileSize($user->getQuota());
			if ($quota === false) {
				return;
			}
			$usage = 100 * ($size / $quota);
			foreach (QuotaWarning::THRESHOLDS as $threshold => $level) {
				if ($usage >= $threshold) {
					$users[$threshold]++;
					break;
				}
			}
		});
		$warnings = [];
		foreach ($users as $threshold => $count) {
			if ($count > 0) {
				$warnings[] = new QuotaWarning($this->l10n, $count, $threshold);
			}
		}
		return $warnings;
	}
}
