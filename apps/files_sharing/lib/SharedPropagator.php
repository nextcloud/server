<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing;

use OC\Files\Cache\Propagator;

class SharedPropagator extends Propagator {
	/**
	 * @var \OC\Files\Storage\Shared
	 */
	protected $storage;

	/**
	 * @param string $internalPath
	 * @param int $time
	 * @param int $sizeDifference
	 */
	public function propagateChange($internalPath, $time, $sizeDifference = 0) {
		/** @var \OC\Files\Storage\Storage $storage */
		list($storage, $sourceInternalPath) = $this->storage->resolvePath($internalPath);
		return $storage->getPropagator()->propagateChange($sourceInternalPath, $time, $sizeDifference);
	}
}
