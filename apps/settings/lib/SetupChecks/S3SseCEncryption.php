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
 * Checks every configured S3 store, in both the legacy single-store and the new-style
 * objectstore map shapes.
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
		// An explicit 'sse' argument always wins, including 'sse' => '' (no encryption),
		// matching the precedence in S3ConnectionTrait::parseEncryptionParams().
		if (array_key_exists('sse', $arguments)) {
			return $arguments['sse'] === 'sse-c';
		}

		// No explicit 'sse' argument: the legacy 'sse_c_key' argument wins over
		// 'sse_kms_enabled' whenever both are set, as it did in Nextcloud 34.
		return !empty($arguments['sse_c_key']);
	}

	/**
	 * Collect the 'arguments' of every S3 store in one objectstore config key.
	 *
	 * Covers both shapes the configuration can take: the legacy single-store form
	 * (`['class' => ..., 'arguments' => [...]]`) and the new-style map, where
	 * 'default'/'root'/'preview' reference named stores such as 'server1' (see
	 * OC\Files\ObjectStore\PrimaryObjectStoreConfig::getObjectStoreConfigs()).
	 * Invalid or non-S3 entries are skipped instead of thrown, so a broken object
	 * store config cannot take the whole setup check down with it.
	 *
	 * @return array[] the 'arguments' of each configured S3 store
	 */
	private function getS3Arguments(string $key): array {
		$config = $this->config->getSystemValue($key, null);
		if (!is_array($config)) {
			return [];
		}

		// The new-style map is recognised by 'default' naming another entry, exactly as
		// PrimaryObjectStoreConfig does; anything else is a single store definition.
		$stores = (isset($config['default']) && is_string($config['default'])) ? $config : [$config];

		$arguments = [];
		foreach ($stores as $store) {
			if (is_array($store) && ($store['class'] ?? null) === 'OC\\Files\\ObjectStore\\S3') {
				$arguments[] = is_array($store['arguments'] ?? null) ? $store['arguments'] : [];
			}
		}

		return $arguments;
	}

	#[\Override]
	public function run(): SetupResult {
		$arguments = array_merge(
			$this->getS3Arguments('objectstore'),
			$this->getS3Arguments('objectstore_multibucket'),
		);

		foreach ($arguments as $argument) {
			if ($this->isSseCInEffect($argument)) {
				return SetupResult::warning(
					$this->l10n->t('This instance uses S3 SSE-C (customer-provided key) encryption, deprecated since Nextcloud 34: Amazon S3 disabled SSE-C by default for new buckets in April 2026. Migrate to SSE-KMS instead.'),
					$this->urlGenerator->linkToDocs('admin-s3-sse-c'),
				);
			}
		}

		return SetupResult::success($this->l10n->t('No deprecated S3 SSE-C encryption configuration found.'));
	}
}
