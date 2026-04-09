<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use Sabre\DAV\Exception;

/**
 * Interface for nodes that can be restored from the trashbin
 */
interface IRestorable {

	/**
	 * Restore this node
	 *
	 * @throws Exception
	 */
	public function restore(): void;
}
