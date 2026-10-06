<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

/**
 * Warns when the primary or multibucket S3 object store is configured to use SSE-C
 * (customer-provided key) server-side encryption, deprecated since Nextcloud 34.
 *
 * Not to be confused with LegacySSEKeyFormat, which checks Nextcloud's own
 * server-side-encryption app's legacy key file format and is unrelated to S3.
 */
class S3SseCEncryption implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('S3 SSE-C encryption');
	}

	#[\Override]
	public function getCategory(): string {
		return 'security';
	}

	/**
	 * @param array $arguments the 'arguments' of an 'objectstore'/'objectstore_multibucket' config
	 */
	private function isSseCInEffect(array $arguments): bool {
		$sse = $arguments['sse'] ?? '';
		if ($sse !== '') {
			return $sse === 'sse-c';
		}

		// No explicit 'sse' argument: matches the precedence in
		// OC\Files\ObjectStore\S3ConnectionTrait::parseEncryptionParams() -- the legacy
		// 'sse_c_key' argument wins over 'sse_kms_enabled' whenever both are set.
		return !empty($arguments['sse_c_key']);
	}

	#[\Override]
	public function run(): SetupResult {
		foreach (['objectstore', 'objectstore_multibucket'] as $key) {
			$objectStore = $this->config->getSystemValue($key, null);
			if (!is_array($objectStore) || ($objectStore['class'] ?? null) !== 'OC\\Files\\ObjectStore\\S3') {
				continue;
			}

			if ($this->isSseCInEffect($objectStore['arguments'] ?? [])) {
				return SetupResult::warning(
					$this->l10n->t('This instance uses S3 SSE-C (customer-provided key) encryption, deprecated since Nextcloud 34: Amazon S3 disabled SSE-C by default for new buckets in April 2026. Migrate to SSE-KMS instead.'),
					$this->urlGenerator->linkToDocs('admin-s3-sse-c'),
				);
			}
		}

		return SetupResult::success($this->l10n->t('No deprecated S3 SSE-C encryption configuration found.'));
	}
}
