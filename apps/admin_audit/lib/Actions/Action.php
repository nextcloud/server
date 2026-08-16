<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Actions;

use OCA\AdminAudit\IAuditLogger;

class Action {

	public function __construct(
		private IAuditLogger $logger,
	) {
	}

	/**
	 * Log a single action with a log level of info
	 *
	 * @param string $text
	 * @param array<string, scalar|null|\DateTimeInterface> $params
	 * @param list<string> $elements
	 * @param bool $obfuscateParameters
	 */
	public function log(
		string $text,
		array $params,
		array $elements,
		bool $obfuscateParameters = false,
	): void {
		foreach ($elements as $element) {
			if (!array_key_exists($element, $params)) {
				$message = '$params["' . $element . '"] was missing.';
				$context = ['app' => 'admin_audit'];

				if (!$obfuscateParameters) {
					$message .= ' Transferred value: {params}';
					$context['params'] = $params;
				}

				$this->logger->critical($message, $context);
				return;
			}
		}

		$replaceArray = [];
		foreach ($elements as $element) {
			$value = $params[$element];
			if ($value instanceof \DateTimeInterface) {
				$value = $value->format('Y-m-d H:i:s');
			}
			$replaceArray[] = $value;
		}

		$this->logger->info(
			vsprintf($text, $replaceArray),
			['app' => 'admin_audit'],
		);
	}
}
