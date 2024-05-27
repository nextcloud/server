<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

class Console extends Action {
	/**
	 * @param array $arguments
	 */
	public function runCommand(array $arguments): void {
		if (!isset($arguments[1]) || $arguments[1] === '_completion') {
			// Don't log autocompletion
			return;
		}

		// Remove `./occ`
		array_shift($arguments);

		$this->log('Console command executed: %s',
			['arguments' => implode(' ', $arguments)],
			['arguments']
		);
	}
}
