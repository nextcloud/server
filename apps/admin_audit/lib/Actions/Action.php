<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Actions;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Operation;

class Action {

	public function __construct(
		private IAuditLogger $logger,
	) {
	}

	/**
	 * Log a single action with a log level of info
	 *
	 * @param Operation|string|null $operation Stable identifier of the action. A string in the form `app.entity.action` is only expected from other apps via CriticalActionPerformedEvent
	 * @param string $text
	 * @param array<string, scalar|null|\DateTimeInterface> $params
	 * @param list<string> $elements
	 * @param bool $obfuscateParameters
	 */
	public function log(
		Operation|string|null $operation,
		string $text,
		array $params,
		array $elements,
		bool $obfuscateParameters = false,
	): void {
		$baseContext = ['app' => 'admin_audit'];
		if ($operation !== null) {
			$baseContext['operation'] = $operation instanceof Operation ? $operation->value : $operation;
		}

		foreach ($elements as $element) {
			if (!array_key_exists($element, $params)) {
				$message = '$params["' . $element . '"] was missing.';
				$context = $baseContext;

				if (!$obfuscateParameters) {
					$message .= ' Transferred value: {params}';
					$context['params'] = $params;
				}

				$this->logger->critical($message, $context);
				return;
			}
		}

		$replaceArray = [];
		$structuredParams = [];
		$context = $baseContext;
		foreach ($elements as $element) {
			$value = $params[$element];
			if ($value instanceof \DateTimeInterface) {
				$value = $value->format('Y-m-d H:i:s');
			}
			$replaceArray[] = $value;
			$structuredParams[$element] = $value;
			// Named {placeholders} are interpolated by the logger
			if (str_contains($text, '{' . $element . '}')) {
				$context[$element] = $value;
			}
		}

		if (!$obfuscateParameters && $structuredParams !== [] && !isset($context['params'])) {
			$context['params'] = $structuredParams;
		}

		$this->logger->info(
			vsprintf($text, $replaceArray),
			$context,
		);
	}
}
