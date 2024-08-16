<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Storage;

use OCP\Files\Storage\IDisableEncryptionStorage;
use Sabre\DAV\Client;

/**
 * Nextcloud backend for external storage based on DAV backend.
 *
 * The Nextcloud URL consists of three parts:
 * http://%host/%context/remote.php/webdav/%root
 *
 */
class OwnCloud extends \OC\Files\Storage\DAV implements IDisableEncryptionStorage {
	public const OC_URL_SUFFIX = 'remote.php/webdav';

	public function __construct($params) {
		// extract context path from host if specified
		// (owncloud install path on host)
		$host = $params['host'];
		// strip protocol
		if (substr($host, 0, 8) === "https://") {
			$host = substr($host, 8);
			$params['secure'] = true;
		} elseif (substr($host, 0, 7) === "http://") {
			$host = substr($host, 7);
			$params['secure'] = false;
		}
		$contextPath = '';
		$hostSlashPos = strpos($host, '/');
		if ($hostSlashPos !== false) {
			$contextPath = substr($host, $hostSlashPos);
			$host = substr($host, 0, $hostSlashPos);
		}

		if (!str_ends_with($contextPath, '/')) {
			$contextPath .= '/';
		}

		if (isset($params['root'])) {
			$root = '/' . ltrim($params['root'], '/');
		} else {
			$root = '/';
		}

		$params['host'] = $host;
		$params['root'] = $contextPath . self::OC_URL_SUFFIX . $root;
		$params['authType'] = Client::AUTH_BASIC;

		parent::__construct($params);
	}

	public function needsPartFile() {
		return false;
	}
}
