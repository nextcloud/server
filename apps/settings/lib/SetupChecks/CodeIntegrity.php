<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('Code integrity');
	}

	#[\Override]
	public function getCategory(): string {
		return 'security';
	}

	#[\Override]
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
						'name' => $this->l10n->t('Raw output …'),
						'link' => $this->urlGenerator->linkToRoute('settings.CheckSetup.getFailedIntegrityCheckFiles'),
					],
					'rescan' => [
						'type' => 'highlight',
						'id' => 'rescanFailedIntegrityCheck',
						'name' => $this->l10n->t('Rescan …'),
						'link' => $this->urlGenerator->linkToRoute('settings.CheckSetup.rescanFailedIntegrityCheck'),
					],
				],
			);
		}
	}
}
