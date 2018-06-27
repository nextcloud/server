<?php
declare(strict_types=1);
/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Versions\Sabre;

use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class DownloadPlugin extends ServerPlugin {

	/** @var Server */
	private $server;

	public function initialize(Server $server) {
		$this->server = $server;

		$this->server->on('afterMethod:GET', [$this,'httpGet']);
	}

	public function httpGet(RequestInterface $request, ResponseInterface $response) {
		$node = $this->server->tree->getNodeForPath($request->getPath());

		if (!($node instanceof VersionFile)) {
			return;
		}

		$filename = $node->getFileName();
		$response->addHeader('Content-Disposition', 'attachment; filename*=UTF-8\'\'' . rawurlencode($filename)
			. '; filename="' . rawurlencode($filename) . '"');
	}

}
