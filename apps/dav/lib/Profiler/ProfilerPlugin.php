<?php declare(strict_types = 1);
/**
 * @copyright 2021 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 *
 * @license AGPL-3.0-or-later
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

namespace OCA\DAV\Profiler;

use OCP\IRequest;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class ProfilerPlugin extends \Sabre\DAV\ServerPlugin {
	private IRequest $request;

	public function __construct(IRequest $request) {
		$this->request = $request;
	}

	/** @return void */
	public function initialize(Server $server) {
		$server->on('afterMethod:*', [$this, 'afterMethod']);
	}

	/** @return void */
	public function afterMethod(RequestInterface $request, ResponseInterface $response) {
		$response->addHeader('X-Debug-Token', $this->request->getId());
	}
}
