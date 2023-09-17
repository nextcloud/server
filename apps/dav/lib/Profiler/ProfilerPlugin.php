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

use OCP\AppFramework\Http\Response;
use OCP\Diagnostics\IEventLogger;
use OCP\IRequest;
use OCP\Profiler\IProfiler;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class ProfilerPlugin extends ServerPlugin {
	private bool $finalized = false;
	public function __construct(
		private IRequest $request,
		private IProfiler $profiler,
		private IEventLogger $eventLogger,
	) {
		$a = 1;
	}

	public function initialize(Server $server): void {
		$server->on('beforeMethod:*', [$this, 'beforeMethod'], 1);
		$server->on('afterMethod:*', [$this, 'afterMethod'], 9999);
		$server->on('afterResponse', [$this, 'afterResponse'], 9999);
		$server->on('exception', [$this, 'exception']);
	}

	public function beforeMethod(): void {
		$this->eventLogger->start('dav:server:method', 'Processing dav request');
	}

	public function afterMethod(RequestInterface $request, ResponseInterface $response): void {
		$this->eventLogger->end('dav:server:method');
		$this->eventLogger->start('dav:server:response', 'Sending dav response');
		if ($this->profiler->isEnabled()) {
			$response->addHeader('X-Debug-Token', $this->request->getId());
		}
	}

	public function afterResponse(RequestInterface $request, ResponseInterface $response): void {
		$this->eventLogger->end('dav:server:response');
		$this->finalize($response->getStatus());
	}

	public function exception(): void {
		$this->finalize();
	}

	public function __destruct() {
		// in error cases, the "afterResponse" isn't called, so we do the finalization now
		$this->finalize();
	}

	public function finalize(int $status = null): void {
		if ($this->finalized) {
			return;
		}
		$this->finalized = true;

		$this->eventLogger->end('runtime');
		if ($this->profiler->isEnabled()) {
			$profile = $this->profiler->collect($this->request, new Response($status));
			$this->profiler->saveProfile($profile);
		}
	}
}
