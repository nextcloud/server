<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
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

namespace OC\Connector\Sabre;

use OC\ServiceUnavailableException;
use OCP\IConfig;
use Sabre\HTTP\RequestInterface;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Server;

/**
 * Class BlockLegacyClientPlugin is used to detect old legacy sync clients and
 * returns a 503 status to those clients.
 *
 * @package OC\Connector\Sabre
 */
class BlockLegacyClientPlugin extends ServerPlugin {
	/** @var Server */
	protected $server;
	/** @var IConfig */
	protected $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @param Server $server
	 * @return void
	 */
	public function initialize(Server  $server) {
		$this->server = $server;
		$this->server->on('beforeMethod', [$this, 'beforeHandler'], 200);
	}

	/**
	 * Detects all unsupported clients and throws a ServiceUnavailableException
	 * which will result in a 503 to them.
	 * @param RequestInterface $request
	 * @throws ServiceUnavailableException If the client version is not supported
	 */
	public function beforeHandler(RequestInterface $request) {
		$userAgent = $request->getHeader('User-Agent');
		$minimumSupportedDesktopVersion = $this->config->getSystemValue('minimum.supported.desktop.version', '1.7.0');

		// Match on the mirall version which is in scheme "Mozilla/5.0 (%1) mirall/%2" or
		// "mirall/%1" for older releases
		preg_match("/(?:mirall\\/)([\d.]+)/i", $userAgent, $versionMatches);
		if(isset($versionMatches[1]) &&
			version_compare($versionMatches[1], $minimumSupportedDesktopVersion) === -1) {
			throw new ServiceUnavailableException('Unsupported client version.');
		}
	}
}
