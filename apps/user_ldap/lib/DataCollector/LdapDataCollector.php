<?php

declare(strict_types = 1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\DataCollector;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\AbstractDataCollector;

class LdapDataCollector extends AbstractDataCollector {
	public function startLdapRequest(string $query, array $args, array $backtrace): void {
		$this->data[] = [
			'start' => microtime(true),
			'query' => $query,
			'args' => $args,
			'end' => microtime(true),
			'backtrace' => $backtrace,
		];
	}

	public function stopLastLdapRequest(): void {
		$this->data[count($this->data) - 1]['end'] = microtime(true);
	}

	public function getName(): string {
		return 'ldap';
	}

	public function collect(Request $request, Response $response, ?\Throwable $exception = null): void {
	}
}
