<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Actions;

use OCA\AdminAudit\Operation;

class Trashbin extends Action {
	public function delete(array $params): void {
		$this->log(Operation::TrashbinFileDeleted, 'File "%s" deleted from trash bin.',
			['path' => $params['path']], ['path']
		);
	}

	public function restore(array $params): void {
		$this->log(Operation::TrashbinFileRestored, 'File "%s" restored from trash bin.',
			['path' => $params['filePath']], ['path']
		);
	}
}
