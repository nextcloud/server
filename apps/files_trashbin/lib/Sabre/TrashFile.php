<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

use OCA\Files_Trashbin\Trashbin;

class TrashFile extends AbstractTrashFile {
	public function get() {
		return $this->data->getStorage()->fopen(Trashbin::getTrashFilename($this->data->getInternalPath(), $this->getDeletionTime()), 'rb');
	}

	public function getName(): string {
		return Trashbin::getTrashFilename($this->data->getName(), $this->getDeletionTime());
	}
}
