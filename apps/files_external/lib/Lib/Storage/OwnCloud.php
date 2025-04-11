<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Lib\Storage;

use OC\Files\Storage\DAV;
use OCP\Files\Storage\IDisableEncryptionStorage;
use Sabre\DAV\Client;

/**
 * Nextcloud backend for external storage based on DAV backend.
 *
 * The Nextcloud URL consists of three parts:
 * http://%host/%context/remote.php/webdav/%root
 *
 */
class OwnCloud extends DAV implements IDisableEncryptionStorage {
	public const OC_URL_SUFFIX = 'remote.php/webdav';

	public function __construct(array $parameters) {
		// extract context path from host if specified
		// (owncloud install path on host)
		$host = $parameters['host'];
		// strip protocol
		if (substr($host, 0, 8) === 'https://') {
			$host = substr($host, 8);
			$parameters['secure'] = true;
		} elseif (substr($host, 0, 7) === 'http://') {
			$host = substr($host, 7);
			$parameters['secure'] = false;
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

		if (isset($parameters['root'])) {
			$root = '/' . ltrim($parameters['root'], '/');
		} else {
			$root = '/';
		}

		$parameters['host'] = $host;
		$parameters['root'] = $contextPath . self::OC_URL_SUFFIX . $root;
		$parameters['authType'] = Client::AUTH_BASIC;

		parent::__construct($parameters);
	}

	public function needsPartFile(): bool {
		return false;
	}
}
