<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

class TrashFolderFile extends AbstractTrashFile {
	public function get() {
		return $this->data->getStorage()->fopen($this->data->getInternalPath(), 'rb');
	}
}
