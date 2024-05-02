<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Adam Williamson <awilliam@redhat.com>
 * @author Aldo "xoen" Giambelluca <xoen@xoen.org>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Brice Maron <brice@bmaron.net>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Frank Karlitschek <frank@karlitschek.de>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philipp Schaffrath <github@philipp.schaffrath.email>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC;

use OCP\HintException;

/**
 * This class is responsible for reading and writing config.php, the very basic
 * configuration file of Nextcloud.
 */
class Config {
	public const ENV_PREFIX = 'NC_';

	/** @var array Associative array ($key => $value) */
	protected $cache = [];
	/** @var array */
	protected $envCache = [];
	/** @var string */
	protected $configDir;
	/** @var string */
	protected $configFilePath;
	/** @var string */
	protected $configFileName;
	/** @var bool */
	protected $isReadOnly;

	/**
	 * @param string $configDir Path to the config dir, needs to end with '/'
	 * @param string $fileName (Optional) Name of the config file. Defaults to config.php
	 */
	public function __construct($configDir, $fileName = 'config.php') {
		$this->configDir = $configDir;
		$this->configFilePath = $this->configDir.$fileName;
		$this->configFileName = $fileName;
		$this->readData();
		$this->isReadOnly = $this->getValue('config_is_read_only', false);
	}

	/**
	 * Lists all available config keys
	 *
	 * Please note that it does not return the values.
	 *
	 * @return array an array of key names
	 */
	public function getKeys() {
		return array_keys($this->cache);
	}

	/**
	 * Returns a config value
	 *
	 * gets its value from an `NC_` prefixed environment variable
	 * if it doesn't exist from config.php
	 * if this doesn't exist either, it will return the given `$default`
	 *
	 * @param string $key key
	 * @param mixed $default = null default value
	 * @return mixed the value or $default
	 */
	public function getValue($key, $default = null) {
		$envKey = self::ENV_PREFIX . $key;
		if (isset($this->envCache[$envKey])) {
			return $this->envCache[$envKey];
		}

		if (isset($this->cache[$key])) {
			return $this->cache[$key];
		}

		return $default;
	}

	/**
	 * Sets and deletes values and writes the config.php
	 *
	 * @param array $configs Associative array with `key => value` pairs
	 *                       If value is null, the config key will be deleted
	 * @throws HintException
	 */
	public function setValues(array $configs) {
		$needsUpdate = false;
		foreach ($configs as $key => $value) {
			if ($value !== null) {
				$needsUpdate |= $this->set($key, $value);
			} else {
				$needsUpdate |= $this->delete($key);
			}
		}

		if ($needsUpdate) {
			// Write changes
			$this->writeData();
		}
	}

	/**
	 * Sets the value and writes it to config.php if required
	 *
	 * @param string $key key
	 * @param mixed $value value
	 * @throws HintException
	 */
	public function setValue($key, $value) {
		if ($this->set($key, $value)) {
			// Write changes
			$this->writeData();
		}
	}

	/**
	 * This function sets the value
	 *
	 * @param string $key key
	 * @param mixed $value value
	 * @return bool True if the file needs to be updated, false otherwise
	 * @throws HintException
	 */
	protected function set($key, $value) {
		if (!isset($this->cache[$key]) || $this->cache[$key] !== $value) {
			// Add change
			$this->cache[$key] = $value;
			return true;
		}

		return false;
	}

	/**
	 * Removes a key from the config and removes it from config.php if required
	 *
	 * @param string $key
	 * @throws HintException
	 */
	public function deleteKey($key) {
		if ($this->delete($key)) {
			// Write changes
			$this->writeData();
		}
	}

	/**
	 * This function removes a key from the config
	 *
	 * @param string $key
	 * @return bool True if the file needs to be updated, false otherwise
	 * @throws HintException
	 */
	protected function delete($key) {
		if (isset($this->cache[$key])) {
			// Delete key from cache
			unset($this->cache[$key]);
			return true;
		}
		return false;
	}

	/**
	 * Loads the config file
	 *
	 * Reads the config file and saves it to the cache
	 *
	 * @throws \Exception If no lock could be acquired or the config file has not been found
	 */
	private function readData() {
		// Default config should always get loaded
		$configFiles = [$this->configFilePath];

		// Add all files in the config dir ending with the same file name
		$extra = glob($this->configDir.'*.'.$this->configFileName);
		if (is_array($extra)) {
			natsort($extra);
			$configFiles = array_merge($configFiles, $extra);
		}

		// Include file and merge config
		foreach ($configFiles as $file) {
			unset($CONFIG);

			// Invalidate opcache (only if the timestamp changed)
			if (function_exists('opcache_invalidate')) {
				opcache_invalidate($file, false);
			}

			$filePointer = @fopen($file, 'r');
			if ($filePointer === false) {
				// e.g. wrong permissions are set
				if ($file === $this->configFilePath) {
					// opening the main config file might not be possible
					// (likely on a new installation)
					continue;
				}

				http_response_code(500);
				die(sprintf('FATAL: Could not open the config file %s', $file));
			}

			// Try to acquire a file lock
			if (!flock($filePointer, LOCK_SH)) {
				throw new \Exception(sprintf('Could not acquire a shared lock on the config file %s', $file));
			}

			try {
				include $file;
			} finally {
				// Close the file pointer and release the lock
				flock($filePointer, LOCK_UN);
				fclose($filePointer);
			}

			if (!defined('PHPUNIT_RUN') && headers_sent()) {
				// syntax issues in the config file like leading spaces causing PHP to send output
				$errorMessage = sprintf('Config file has leading content, please remove everything before "<?php" in %s', basename($file));
				if (!defined('OC_CONSOLE')) {
					print(\OCP\Util::sanitizeHTML($errorMessage));
				}
				throw new \Exception($errorMessage);
			}
			if (isset($CONFIG) && is_array($CONFIG)) {
				$this->cache = array_merge($this->cache, $CONFIG);
			}
		}

		$this->envCache = getenv();
	}

	/**
	 * Writes the config file
	 *
	 * Saves the config to the config file.
	 *
	 * @throws HintException If the config file cannot be written to
	 * @throws \Exception If no file lock can be acquired
	 */
	private function writeData() {
		$this->checkReadOnly();

		if (!is_file(\OC::$configDir.'/CAN_INSTALL') && !isset($this->cache['version'])) {
			throw new HintException(sprintf('Configuration was not read or initialized correctly, not overwriting %s', $this->configFilePath));
		}

		// Create a php file ...
		$content = "<?php\n";
		$content .= '$CONFIG = ';
		$content .= var_export($this->cache, true);
		$content .= ";\n";

		touch($this->configFilePath);
		$filePointer = fopen($this->configFilePath, 'r+');

		// Prevent others not to read the config
		chmod($this->configFilePath, 0640);

		// File does not exist, this can happen when doing a fresh install
		if (!is_resource($filePointer)) {
			throw new HintException(
				"Can't write into config directory!",
				'This can usually be fixed by giving the webserver write access to the config directory.');
		}

		// Never write file back if disk space should be too low
		if (function_exists('disk_free_space')) {
			$df = disk_free_space($this->configDir);
			$size = strlen($content) + 10240;
			if ($df !== false && $df < (float)$size) {
				throw new \Exception($this->configDir . " does not have enough space for writing the config file! Not writing it back!");
			}
		}

		// Try to acquire a file lock
		if (!flock($filePointer, LOCK_EX)) {
			throw new \Exception(sprintf('Could not acquire an exclusive lock on the config file %s', $this->configFilePath));
		}

		// Write the config and release the lock
		ftruncate($filePointer, 0);
		fwrite($filePointer, $content);
		fflush($filePointer);
		flock($filePointer, LOCK_UN);
		fclose($filePointer);

		if (function_exists('opcache_invalidate')) {
			@opcache_invalidate($this->configFilePath, true);
		}
	}

	/**
	 * @throws HintException
	 */
	private function checkReadOnly(): void {
		if ($this->isReadOnly) {
			throw new HintException(
				'Config is set to be read-only via option "config_is_read_only".',
				'Unset "config_is_read_only" to allow changes to the config file.');
		}
	}
}
