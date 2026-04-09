<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Cache;

use OCP\Files\Storage\IStorage;
use OCP\IDBConnection;

class HomePropagator extends Propagator {
	public function __construct(IStorage $storage, IDBConnection $connection) {
		parent::__construct($storage, $connection, ignore: ['files_encryption']);
	}
}
