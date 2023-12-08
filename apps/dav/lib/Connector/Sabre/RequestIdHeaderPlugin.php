<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Robin Appelman <robin@icewind.nl>
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

namespace OCA\DAV\Connector\Sabre;

use OCP\IRequest;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class RequestIdHeaderPlugin extends \Sabre\DAV\ServerPlugin {
	/** @var IRequest */
	private $request;

	public function __construct(IRequest $request) {
		$this->request = $request;
	}

	public function initialize(\Sabre\DAV\Server $server) {
		$server->on('afterMethod:*', [$this, 'afterMethod']);
	}

	/**
	 * Add the request id as a header in the response
	 *
	 * @param RequestInterface $request request
	 * @param ResponseInterface $response response
	 */
	public function afterMethod(RequestInterface $request, ResponseInterface $response) {
		$response->setHeader('X-Request-Id', $this->request->getId());
	}
}
