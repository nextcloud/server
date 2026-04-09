<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Sabre;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\IFile;

abstract class AbstractTrashFile extends AbstractTrash implements IFile, ITrash {
	public function put($data) {
		throw new Forbidden();
	}

	public function setName($name) {
		throw new Forbidden();
	}
}
