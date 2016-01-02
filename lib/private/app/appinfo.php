<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\App;

use OCP\App\IAppInfo;

/**
 * App metadata
 *
 * @since 9.0.0
 */
class AppInfo implements IAppInfo {
	/**
	 * @var string
	 */
	private $file;

	/**
	 * Parsed appinfo
	 *
	 * @var array
	 */
	private $data;

	/**
	 * App version
	 *
	 * @var int
	 */
	private $version;

	/**
	 * @var InfoParser
	 */
	private $parser;

	/**
	 * @var string
	 */
	private $appId;

	/**
	 * @var null|string
	 */
	private $versionFile;

	/**
	 * AppInfo constructor.
	 *
	 * @param string $appId
	 * @param string $file
	 * @param InfoParser $parser
	 * @param string|null $versionFile
	 * @param array $data
	 */
	public function __construct($appId, $file, InfoParser $parser, $versionFile = null, $data = []) {
		$this->appId = $appId;
		$this->file = $file;
		$this->parser = $parser;
		$this->versionFile = $versionFile;
		$this->data = $data;
	}

	private function parse() {
		if (!$this->data) {
			$this->data = $this->fixAppDescription($this->parser->parse($this->file));

			if (isset($this->data['ocsid'])) {
				$storedId = \OC::$server->getConfig()->getAppValue($this->appId, 'ocsid');
				if ($storedId !== '' && $storedId !== $this->data['ocsid']) {
					$this->data['ocsid'] = $storedId;
				}
			}
			if (!isset($this->data['version'])) {
				$this->data['version'] = $this->getVersion();
			} else {
				$this->version = $this->data['version'];
			}
		}
	}

	private function fixAppDescription($data) {
		// just modify the description if it is available
		// otherwise this will create a $data element with an empty 'description'
		if (isset($data['description'])) {
			if (is_string($data['description'])) {
				// sometimes the description contains line breaks and they are then also
				// shown in this way in the app management which isn't wanted as HTML
				// manages line breaks itself

				// first of all we split on empty lines
				$paragraphs = preg_split("!\n[[:space:]]*\n!mu", $data['description']);

				$result = [];
				foreach ($paragraphs as $value) {
					// replace multiple whitespace (tabs, space, newlines) inside a paragraph
					// with a single space - also trims whitespace
					$result[] = trim(preg_replace('![[:space:]]+!mu', ' ', $value));
				}

				// join the single paragraphs with a empty line in between
				$data['description'] = implode("\n\n", $result);

			} else {
				$data['description'] = '';
			}
		}
		return $data;
	}

	/**
	 * extract the version without doing full xml parsing
	 */
	private function parseVersion() {
		if (!$this->version) {
			if (isset($this->data['version'])) {
				$this->version = $this->data['version'];
			} else if ($this->versionFile && file_exists($this->versionFile)) {
				$this->version = trim(file_get_contents($this->versionFile));
			} else if (file_exists($this->file)) {
				$regex = '/\<version\>([0-9.]+)<\/version\>/';
				if (preg_match($regex, file_get_contents($this->file), $matches)) {
					$this->version = $matches[1];
				}
			} else {
				$this->version = '0';
			}
		}
	}

	public function getAppId() {
		return $this->appId;
	}

	public function getVersion() {
		$this->parseVersion();
		return $this->version;
	}

	public function toArray() {
		$this->parse();
		return $this->data;
	}
}
