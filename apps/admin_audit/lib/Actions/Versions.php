<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

class Versions extends Action {
	public function rollback(array $params): void {
		$this->log('Version "%s" of "%s" was restored.',
			[
				'version' => $params['revision'],
				'path' => $params['path']
			],
			['version', 'path']
		);
	}

	public function delete(array $params): void {
		$this->log('Version "%s" was deleted.',
			['path' => $params['path']],
			['path']
		);
	}
}
