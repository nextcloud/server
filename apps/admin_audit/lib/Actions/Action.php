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
	 * @param array $params
	 * @param array $elements
	 * @param bool $obfuscateParameters
	 */
	public function log(string $text,
		array $params,
		array $elements,
		bool $obfuscateParameters = false): void {
		foreach ($elements as $element) {
			if (!isset($params[$element])) {
				if ($obfuscateParameters) {
					$this->logger->critical(
						'$params["' . $element . '"] was missing.',
						['app' => 'admin_audit']
					);
				} else {
					$this->logger->critical(
						'$params["' . $element . '"] was missing. Transferred value: {params}',
						['app' => 'admin_audit', 'params' => $params]
					);
				}
				return;
			}
		}

		$replaceArray = [];
		foreach ($elements as $element) {
			if ($params[$element] instanceof \DateTime) {
				$params[$element] = $params[$element]->format('Y-m-d H:i:s');
			}
			$replaceArray[] = $params[$element];
		}

		$this->logger->info(
			vsprintf(
				$text,
				$replaceArray
			),
			[
				'app' => 'admin_audit'
			]
		);
	}
}
