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
		} elseif ($this->checker->hasPassedCheck()) {
			return SetupResult::success($this->l10n->t('No altered files'));
		} else {
			$completeResults = $this->checker->getResults();
			$formattedTextResponse = '';
			if (!empty($completeResults)) {
				$formattedTextResponse = '#### ' . $this->l10n->t('Technical information');
				$formattedTextResponse .= "\n" . $this->l10n->t('The following list covers which files have failed the integrity check.');
				$formattedTextResponse .= "\n";
				foreach ($completeResults as $context => $contextResult) {
					$formattedTextResponse .= "- $context\n";
	
					foreach ($contextResult as $category => $result) {
						$categoryName = match($category) {
							'EXCEPTION' => $this->l10n->t('Exception'),
							'EXTRA_FILE' => $this->l10n->t('Unexpected file'),
							'FILE_MISSING' => $this->l10n->t('Missing file'),
							'INVALID_HASH' => $this->l10n->t('Invalid file (hash mismatch)'),
							default => $category,
						};
						$formattedTextResponse .= "\t- $categoryName\n";
						if ($category !== 'EXCEPTION') {
							foreach ($result as $key => $results) {
								$formattedTextResponse .= "\t\t- $key\n";
							}
						} else {
							foreach ($result as $key => $results) {
								$formattedTextResponse .= "\t\t- $results\n";
							}
						}
					}
				}
			}

			return SetupResult::error(
				$this->l10n->t('Some files have not passed the integrity check. {rawOutput} {rescan}') . "\n\n" . $formattedTextResponse,
				$this->urlGenerator->linkToDocs('admin-code-integrity'),
				[
					'rawOutput' => [
						'type' => 'highlight',
						'id' => 'getFailedIntegrityCheckFiles',
						'name' => $this->l10n->t('Raw output…'),
						'link' => $this->urlGenerator->linkToRoute('settings.CheckSetup.getFailedIntegrityCheckFiles'),
					],
					'rescan' => [
						'type' => 'highlight',
						'id' => 'rescanFailedIntegrityCheck',
						'name' => $this->l10n->t('Rescan…'),
						'link' => $this->urlGenerator->linkToRoute('settings.CheckSetup.rescanFailedIntegrityCheck'),
					],
				],
			);
		}
	}
}
