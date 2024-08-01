<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Cache\Wrapper;

use OC\Files\Cache\Propagator;
use OC\Files\Storage\Wrapper\Jail;

class JailPropagator extends Propagator {
	/**
	 * @var Jail
	 */
	protected $storage;

	/**
	 * @param string $internalPath
	 * @param int $time
	 * @param int $sizeDifference
	 */
	public function propagateChange($internalPath, $time, $sizeDifference = 0) {
		/** @var \OC\Files\Storage\Storage $storage */
		[$storage, $sourceInternalPath] = $this->storage->resolvePath($internalPath);
		$storage->getPropagator()->propagateChange($sourceInternalPath, $time, $sizeDifference);
	}
}
