<?php

namespace OC\Config\Model;

use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * @since 32.0.0
 */
class PresetDefault {
	private array $preset = [];
	public function __construct(
		private readonly string $presetFile,
		private readonly bool $invalidate = false,
	) {
		$this->loadEntries();
	}

	public function getKeys(string $appId): array {
		return array_keys($this->preset[$appId] ?? []);
	}

	public function isAppConfigEnforced(string $appId, string $key): bool {
		return (($this->preset[$appId]['appConfig']['!' . $key] ?? null) !== null);
	}

 	public function getAppConfigDefault(string $appId, string $key): ?string {
		return $this->preset[$appId]['appConfig']['!' . $key] ?? $this->preset[$appId]['appConfig'][$key] ?? null;
	}

	public function isUserConfigEnforced(string $appId, string $key): bool {
		return (($this->preset[$appId]['userConfig']['!' . $key] ?? null) !== null);
	}

 	public function getUserConfigDefault(string $appId, string $key): ?string {
		return $this->preset[$appId]['userConfig'][$key] ?? null;
	}

	private function loadEntries(): void {
		if ($this->invalidate && function_exists('opcache_invalidate')) {
			@opcache_invalidate($this->presetFile, false);

		}

		$fp = (file_exists($this->presetFile) ? fopen($this->presetFile, 'r') : false);
		if (!$fp) {
			Server::get(LoggerInterface::class)->warning(sprintf('Preset file %s does not exist', $this->presetFile));
			return;
		}

		if (!flock($fp, LOCK_SH)) {
			Server::get(LoggerInterface::class)->warning(sprintf('Could not acquire a shared lock on preset file %s', $this->presetFile));
			return;
		}

		try {
			$alreadySent = (!defined('PHPUNIT_RUN') && headers_sent());
			$preset = include $this->presetFile;
		} finally {
			flock($fp, LOCK_UN);
			fclose($fp);
		}

		if (!$alreadySent && !defined('PHPUNIT_RUN') && headers_sent()) {
			Server::get(LoggerInterface::class)->warning(sprintf('Preset file has leading content, please remove everything before "<?php" in %s', $this->presetFile));
			throw new \Exception('preset file has leading content.');
		}

		if ($preset && is_array($preset)) {
			$this->preset = $preset;
		}
	}
}
