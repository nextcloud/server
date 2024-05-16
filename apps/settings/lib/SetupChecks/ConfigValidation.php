<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class ConfigValidation implements ISetupCheck {
	public const FAKE_DEFAULT_VALUE = '#$HOPEFULLY_NOONE_USES_THIS_DEFAULT_VALUE$#';

	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
	) {
	}

	public function getCategory(): string {
		return 'config';
	}

	public function getName(): string {
		return $this->l10n->t('Config validation');
	}

	public function run(): SetupResult {
		$proxyuserpwd = $this->config->getSystemValue('proxyuserpwd', self::FAKE_DEFAULT_VALUE);
		if ($proxyuserpwd !== self::FAKE_DEFAULT_VALUE) {
			if (!is_string($proxyuserpwd)) {
				return SetupResult::error($this->l10n->t('Config value %s is not a string value.', ['proxyuserpwd']));
			}

			$parts = array_filter(explode(':', $proxyuserpwd));
			if (count($parts) !== 2) {
				return SetupResult::error($this->l10n->t('Config value %s is not well formatted, expected format: non-empty-string, colon, non-empty-string (sample: "user:password").', ['proxyuserpwd']));
			}
		}

		$overwritehost = $this->config->getSystemValue('overwritehost', self::FAKE_DEFAULT_VALUE);
		if ($overwritehost !== self::FAKE_DEFAULT_VALUE) {
			$return = $this->checkDomainOnly('overwritehost', $overwritehost);
			if ($return) {
				return $return;
			}
		}

		$trustedDomains = $this->config->getSystemValue('trusted_domains', self::FAKE_DEFAULT_VALUE);
		if ($trustedDomains !== self::FAKE_DEFAULT_VALUE) {
			if (!is_array($trustedDomains)) {
				return SetupResult::error($this->l10n->t('Config value %s must be a list of strings with at least 1 entry.', ['trusted_domains']));
			}

			foreach ($trustedDomains as $key => $trustedDomain) {
				if (!is_int($key)) {
					return SetupResult::error($this->l10n->t('Config value %s must be a list of strings, but found a non-numeric key.', ['trusted_domains']));
				}

				// Resolve wildcards
				$trustedDomain = str_replace('*', '1', $trustedDomain);

				$return = $this->checkDomainWithPort('trusted_domains => ' . $key, $trustedDomain);
				if ($return) {
					return $return;
				}
			}
		} else {
			return SetupResult::error($this->l10n->t('Config value %s must be a list of strings with at least 1 entry.', ['trusted_domains']));
		}

		$trustedProxies = $this->config->getSystemValue('trusted_proxies', self::FAKE_DEFAULT_VALUE);
		if ($trustedProxies !== self::FAKE_DEFAULT_VALUE) {
			if (!is_array($trustedProxies)) {
				return SetupResult::error($this->l10n->t('Config value %s must be a list of strings.', ['trusted_proxies']));
			}

			foreach ($trustedProxies as $key => $trustedProxy) {
				if (!is_int($key)) {
					return SetupResult::error($this->l10n->t('Config value %s must be a list of strings, but found a non-numeric key.', ['trusted_proxies']));
				}

				$return = $this->checkIPOrIPRange('trusted_proxies => ' . $key, $trustedProxy);
				if ($return) {
					return $return;
				}
			}
		}

		$fullURLConfigs = [
			'overwrite.cli.url',
			'lost_password_link',
			'updater.server.url',
			'logo_url',
			'appstoreurl',
			'upgrade.cli-upgrade-link',
			'preview_imaginary_url',
			'lookup_server',
			'customclient_desktop',
			'customclient_android',
			'customclient_ios',
		];
		foreach ($fullURLConfigs as $configKey) {
			$url = $this->config->getSystemValue($configKey, self::FAKE_DEFAULT_VALUE);
			if ($url === self::FAKE_DEFAULT_VALUE) {
				continue;
			}

			$parsed = parse_url($url);
			if ($parsed === false) {
				return SetupResult::error($this->l10n->t('Config value %s is not a valid URL.', [$configKey]));
			}

			$scheme = $parsed['scheme'] ?? '';
			if ($scheme !== 'https' && $scheme !== 'http') {
				return SetupResult::error($this->l10n->t('Config value %s should be a full URL but has no protocol.', [$configKey]));
			}

			if (isset($parsed['user']) || isset($parsed['pass'])) {
				return SetupResult::error($this->l10n->t('Config value %s should not contain user nor password.', [$configKey]));
			}
		}

		$booleanConfigs = [
			'auth.bruteforce.protection.enabled' => true,
			'auth.bruteforce.protection.testing' => false,
			'ratelimit.protection.enabled' => true,
		];

		foreach ($booleanConfigs as $configKey => $expectedValue) {
			$result = $this->checkBoolean($configKey, $expectedValue);
			if ($result) {
				return $result;
			}
		}

		return SetupResult::success($this->l10n->t('All configuration values look OK'));
	}

	protected function checkBoolean(string $configKey, bool $shouldBe): ?SetupResult {
		$value = $this->config->getSystemValue($configKey, self::FAKE_DEFAULT_VALUE);
		if ($value === self::FAKE_DEFAULT_VALUE) {
			return null;
		}

		if (!is_bool($value)) {
			return SetupResult::error($this->l10n->t('Config value %s is not a boolean value.', [$configKey]));
		}

		if ($value !== $shouldBe) {
			return SetupResult::warning($this->l10n->t('Config value %s should be %s in production instances.', [$configKey, json_encode($shouldBe)]));
		}
		return null;
	}

	protected function checkDomainOnly(string $configKey, mixed $configValue): ?SetupResult {
		if (!is_string($configValue)) {
			return SetupResult::error($this->l10n->t('Config value %s is not a string value.', [$configKey]));
		}

		if (str_starts_with($configValue, 'http:') || str_starts_with($configValue, 'https:')) {
			return SetupResult::error($this->l10n->t('Config value %s should not contain a protocol.', [$configKey]));
		}

		$url = 'https://' . $configValue;
		$parts = parse_url($url);
		if (!isset($parts['scheme'], $parts['host']) || count($parts) !== 2) {
			return SetupResult::error($this->l10n->t('Config value %s should only contain a domain.', [$configKey]));
		}

		return null;
	}

	protected function checkDomainWithPort(string $configKey, mixed $configValue): ?SetupResult {
		if (!is_string($configValue)) {
			return SetupResult::error($this->l10n->t('Config value %s is not a string value.', [$configKey]));
		}

		if (str_starts_with($configValue, 'http:') || str_starts_with($configValue, 'https:')) {
			return SetupResult::error($this->l10n->t('Config value %s should not contain a protocol.', [$configKey]));
		}

		$url = 'https://' . $configValue;
		$parts = parse_url($url);
		if ($parts === false || !isset($parts['scheme'], $parts['host']) || (count($parts) !== 2 && count($parts) !== 3)) {
			return SetupResult::error($this->l10n->t('Config value %s should contain a domain or a domain and port.', [$configKey]));
		}
		if (!isset($parts['port']) && count($parts) === 3) {
			return SetupResult::error($this->l10n->t('Config value %s should only contain a domain or domain and port.', [$configKey]));
		}

		return null;
	}

	protected function checkIPOrIPRange(string $configKey, mixed $configValue): ?SetupResult {
		if (!is_string($configValue)) {
			return SetupResult::error($this->l10n->t('Config value %s is not a string value.', [$configKey]));
		}

		$parts = explode('/', $configValue);
		if (count($parts) > 2) {
			return SetupResult::error($this->l10n->t('Config value %s is not an valid IP address or IP range.', [$configKey]));
		}

		if (count($parts) === 1) {
			if (filter_var($parts[0], FILTER_VALIDATE_IP) === false) {
				return SetupResult::error($this->l10n->t('Config value %s is not an valid IP address or IP range.', [$configKey]));
			}
			return null;
		}

		if (filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			if (!is_numeric($parts[1]) || $parts[1] > 32 || $parts[1] < 1) {
				return SetupResult::error($this->l10n->t('Config value %s is not an valid IP address or IP range.', [$configKey]));
			}
		} elseif (filter_var($parts[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
			if (!is_numeric($parts[1]) || $parts[1] > 128 || $parts[1] < 1) {
				return SetupResult::error($this->l10n->t('Config value %s is not an valid IP address or IP range.', [$configKey]));
			}
		} else {
			return SetupResult::error($this->l10n->t('Config value %s is not an valid IP address or IP range.', [$configKey]));
		}
		return null;
	}
}
