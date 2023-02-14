<?php

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
