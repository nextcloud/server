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
namespace OCA\Settings\SetupChecks;

use OC\IntegrityCheck\Checker;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class CodeIntegrity implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private Checker $checker,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Code integrity');
	}

	public function getCategory(): string {
		return 'security';
	}

	public function run(): SetupResult {
		if (!$this->checker->isCodeCheckEnforced()) {
			return SetupResult::info($this->l10n->t('Integrity checker has been disabled. Integrity cannot be verified.'));
		}

		// If there are no results we need to run the verification
		if ($this->checker->getResults() === null) {
			$this->checker->runInstanceVerification();
		}

		if ($this->checker->hasPassedCheck()) {
			return SetupResult::success($this->l10n->t('No altered files'));
		} else {
			return SetupResult::error(
				$this->l10n->t('Some files have not passed the integrity check. {link1} {link2}'),
				$this->urlGenerator->linkToDocs('admin-code-integrity'),
				[
					'link1' => [
						'type' => 'highlight',
						'id' => 'getFailedIntegrityCheckFiles',
						'name' => 'List of invalid files…',
						'link' => $this->urlGenerator->linkToRoute('settings.CheckSetup.getFailedIntegrityCheckFiles'),
					],
					'link2' => [
						'type' => 'highlight',
						'id' => 'rescanFailedIntegrityCheck',
						'name' => 'Rescan…',
						'link' => $this->urlGenerator->linkToRoute('settings.CheckSetup.rescanFailedIntegrityCheck'),
					],
				],
			);
		}
	}
}
