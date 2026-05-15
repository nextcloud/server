<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

use OCA\AdminAudit\IAuditLogger;

/**
 * Base class for audit logging actions
 *
 * Provides structured logging for admin audit events with type-safe parameter handling
 */
class Action {
	public function __construct(
		private readonly IAuditLogger $logger,
	) {
	}

	/**
	 * Log a single action with a log level of info.
	 *
	 * Example usage:
	 *   $this->log(
	 *       'User "%s" added to group "%s"',
	 *       ['user' => 'alice', 'group' => 'admins'],
	 *       ['user', 'group']
	 *   );
	 *
	 * @param string $messageTemplate Format string for vsprintf (e.g., "User %s deleted file %s")
	 * @param array $data Associative array of data values
	 * @param array $requiredKeys Array of keys that must exist in $data; order must match format placeholders
	 * @param bool $excludeSensitiveData If true, omit parameter details from error logs
	 */
	public function log(
		string $messageTemplate,
		array $data,
		array $requiredKeys,
		bool $excludeSensitiveData = false
	): void {
		$missingKeys = [];
		foreach ($requiredKeys as $key) {
			if (!isset($data[$key])) {
				$missingKeys[] = $key;
			}
		}

		if (!empty($missingKeys)) {
			$context = ['app' => 'admin_audit', 'missing_keys' => $missingKeys];
	
			if (!$excludeSensitiveData) {
				$context['provided_keys'] = array_keys($data);
			}
			
			$this->logger->critical(
				'Required audit parameters missing: {missing_keys}',
				$context
			);
			return;
		}

		$replacementValues = [];
		foreach ($requiredKeys as $key) {
			$value = $data[$key];

			// Handle different types safely
			if ($value instanceof \DateTime) {
				$replacementValues[] = $value->format('Y-m-d H:i:s');
			} elseif (is_bool($value)) {
				$replacementValues[] = $value ? 'true' : 'false';
			} elseif (is_scalar($value)) {
				$replacementValues[] = (string)$value;
			} elseif ($value === null) {
				$replacementValues[] = 'null';
			} else {
				$replacementValues[] = json_encode($value, JSON_UNESCAPED_SLASHES) ?: gettype($value);
			}
		}

		try {
			$message = vsprintf($messageTemplate, $replacementValues);
			$this->logger->info($message, ['app' => 'admin_audit']);
		} catch (\ValueError $e) {
			// vsprintf throws ValueError in PHP 8+ when format/argument mismatch occurs
			$this->logger->critical(
				'Audit log format string mismatch: {error}',
				[
					'app' => 'admin_audit',
					'error' => $e->getMessage(),
					'format' => $messageTemplate,
					'element_count' => count($requiredKeys)
				]
			);
		}
	}
}
