<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OC\AllConfig;
use OC\Security\Crypto;
use phpseclib3\Crypt\AES;

// CLI only: no instance bootstrap, database, configuration or vault data.
if (PHP_SAPI !== 'cli') {
	exit(1);
}

error_reporting(0);
ob_start();

/** @return array{Crypto, AES} */
function createSyntheticCrypto(): array {
	$config = new class extends AllConfig {
		public function __construct() {
		}

		public function getSystemValue($key, $default = '') {
			return $default;
		}

		public function getSystemValueString(string $key, string $default = ''): string {
			return $default;
		}
	};
	$cipher = new class('cbc') extends AES {
		public int $calls = 0;
		public float $seconds = 0;

		public function setPassword($password, $method = 'pbkdf2', ...$arguments) {
			$start = hrtime(true);
			$result = parent::setPassword($password, $method, ...$arguments);
			$this->seconds += (hrtime(true) - $start) / 1e9;
			$this->calls++;
			return $result;
		}
	};
	$crypto = new Crypto($config);
	(new ReflectionProperty(Crypto::class, 'cipher'))->setValue($crypto, $cipher);
	return [$crypto, $cipher];
}

try {
	$root = realpath($argv[1] ?? dirname(__DIR__, 2));
	$source = realpath($argv[2] ?? $root . '/lib/private/Security/Crypto.php');
	if ($root === false || $source === false || !is_file($source)
		|| !is_file($root . '/lib/composer/autoload.php')
		|| !is_file($root . '/3rdparty/autoload.php')) {
		throw new RuntimeException('Nextcloud sources and third-party dependencies are required');
	}
	require_once $root . '/lib/composer/autoload.php';
	require_once $root . '/3rdparty/autoload.php';
	require_once $source;

	$report = [
		'php_version' => PHP_VERSION,
		'crypto_sha256' => hash_file('sha256', $source),
		'data' => 'synthetic only',
		'fields_per_revision' => 6,
		'samples' => [],
	];
	[$writer] = createSyntheticCrypto();
	foreach ([1, 100, 344] as $count) {
		$fixtures = [];
		for ($revision = 0; $revision < $count; $revision++) {
			$key = str_repeat('Synthetic key material / ', 128) . $revision;
			$fields = [];
			for ($field = 0; $field < 6; $field++) {
				$fields[] = $writer->encrypt('Synthetic field ' . $field, $key);
			}
			$fixtures[] = [$key, $fields];
		}

		$runs = [];
		for ($round = 0; $round < 3; $round++) {
			[$reader, $cipher] = createSyntheticCrypto();
			$start = hrtime(true);
			foreach ($fixtures as [$key, $fields]) {
				foreach ($fields as $field => $value) {
					if ($reader->decrypt($value, $key) !== 'Synthetic field ' . $field) {
						throw new RuntimeException('Fixture mismatch');
					}
				}
			}
			$runs[] = [
				'wall_ms' => round((hrtime(true) - $start) / 1e6, 2),
				'set_password_calls' => $cipher->calls,
				'set_password_ms' => round($cipher->seconds * 1000, 2),
			];
		}
		$report['samples'][] = ['revisions' => $count, 'runs' => $runs];
	}
	$report['fixture_comparisons_passed'] = true;
	ob_end_clean();
	echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
} catch (Throwable) {
	while (ob_get_level()) {
		ob_end_clean();
	}
	echo json_encode(['error' => 'benchmark_failed']) . "\n";
	exit(1);
}
