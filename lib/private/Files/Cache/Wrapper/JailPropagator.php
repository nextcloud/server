<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache\Wrapper;

use OC\Files\Cache\Propagator;
use OC\Files\Storage\Wrapper\Jail;
use Override;

class JailPropagator extends Propagator {
	#[Override]
	public function propagateChange(string $internalPath, int $time, int $sizeDifference = 0): void {
		/** @var Jail $jail */
		$jail = $this->storage;
		[$storage, $sourceInternalPath] = $jail->resolvePath($internalPath);
		$storage->getPropagator()->propagateChange($sourceInternalPath, $time, $sizeDifference);
	}
}
