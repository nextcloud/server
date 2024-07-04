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
