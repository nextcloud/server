<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use bantu\IniGetWrapper\IniGetWrapper;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use OCP\Util;

class PhpMaxFileSize implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IniGetWrapper $iniGetWrapper,
	) {
	}

	public function getCategory(): string {
		return 'php';
	}

	public function getName(): string {
		return $this->l10n->t('PHP file size upload limit');
	}

	public function run(): SetupResult {
		$upload_max_filesize = (string)$this->iniGetWrapper->getString('upload_max_filesize');
		$post_max_size = (string)$this->iniGetWrapper->getString('post_max_size');
		$max_input_time = (int)$this->iniGetWrapper->getString('max_input_time');
		$max_execution_time = (int)$this->iniGetWrapper->getString('max_execution_time');

		$warnings = [];
		$recommendedSize = 16 * 1024 * 1024 * 1024;
		$recommendedTime = 3600;

		// Check if the PHP upload limit is too low
		if (Util::computerFileSize($upload_max_filesize) < $recommendedSize) {
			$warnings[] = $this->l10n->t('The PHP upload_max_filesize is too low. A size of at least %1$s is recommended. Current value: %2$s.', [
				Util::humanFileSize($recommendedSize),
				$upload_max_filesize,
			]);
		}
		if (Util::computerFileSize($post_max_size) < $recommendedSize) {
			$warnings[] = $this->l10n->t('The PHP post_max_size is too low. A size of at least %1$s is recommended. Current value: %2$s.', [
				Util::humanFileSize($recommendedSize),
				$post_max_size,
			]);
		}

		// Check if the PHP execution time is too low
		if ($max_input_time < $recommendedTime && $max_input_time !== -1) {
			$warnings[] = $this->l10n->t('The PHP max_input_time is too low. A time of at least %1$s is recommended. Current value: %2$s.', [
				$recommendedTime,
				$max_input_time,
			]);
		}

		if ($max_execution_time < $recommendedTime && $max_execution_time !== -1) {
			$warnings[] = $this->l10n->t('The PHP max_execution_time is too low. A time of at least %1$s is recommended. Current value: %2$s.', [
				$recommendedTime,
				$max_execution_time,
			]);
		}

		if (!empty($warnings)) {
			return SetupResult::warning(join(' ', $warnings), $this->urlGenerator->linkToDocs('admin-big-file-upload'));
		}

		return SetupResult::success();
	}
}
