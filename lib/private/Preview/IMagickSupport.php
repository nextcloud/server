<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Preview;

use OCP\ICache;
use OCP\ICacheFactory;

class IMagickSupport {
	private ICache $cache;
	private ?\Imagick $imagick;

	public function __construct(ICacheFactory $cacheFactory) {
		$this->cache = $cacheFactory->createLocal('imagick');

		if (extension_loaded('imagick')) {
			$this->imagick = new \Imagick();
		} else {
			$this->imagick = null;
		}
	}

	public function hasExtension(): bool {
		return !is_null($this->imagick);
	}

	public function supportsFormat(string $format): bool {
		if (is_null($this->imagick)) {
			return false;
		}

		$cached = $this->cache->get($format);
		if (!is_null($cached)) {
			return $cached;
		}

		$formatSupported = count($this->imagick->queryFormats($format)) === 1;
		$this->cache->set($format, $cached);
		return $formatSupported;
	}
}
