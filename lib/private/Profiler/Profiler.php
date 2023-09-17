<?php

declare(strict_types=1);

/**
 * @copyright 2021 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Profiler;

use OC\AppFramework\Http\Request;
use OC\AppFramework\Http\RequestVars;
use OC\Profiler\DataCollector\HttpDataCollector;
use OC\Profiler\DataCollector\MemoryDataCollector;
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\IDataCollector;
use OCP\IRequest;
use OCP\Profiler\IProfiler;
use OCP\Profiler\IProfile;
use OC\SystemConfig;

class Profiler implements IProfiler {
	/** @var array<string, IDataCollector> */
	private array $dataCollectors = [];

	private ?FileProfilerStorage $storage = null;

	private bool $enabled = false;

	/**
	 * we inject the container, else we get a loop with the IRequest
	 */
	public function __construct(SystemConfig $config, RequestVars $request) {
		$this->setEnabled($this->shouldProfilerBeEnabled($config, $request));
		$this->storage = new FileProfilerStorage($config->getValue('datadirectory', \OC::$SERVERROOT . '/data') . '/profiler');
	}

	private function shouldProfilerBeEnabled(SystemConfig $config, RequestVars $request): bool {
		if ($config->getValue('profiler', false)) {
			return true;
		}
		$condition = $config->getValue('profiler.condition', []);
		if (isset($condition['shared_secret'])) {
			if ($request->getMethod() === 'PUT' &&
				!str_contains($request->getHeader('Content-Type'), 'application/x-www-form-urlencoded') &&
				!str_contains($request->getHeader('Content-Type'), 'application/json')) {
				$logSecretRequest = '';
			} else {
				$logSecretRequest = $request->getParam('profiler_secret', '');
			}

			// if token is found in the request change set the log condition to satisfied
			if (hash_equals($condition['shared_secret'], $logSecretRequest)) {
				return true;
			}
		}
		return false;
	}

	public function add(IDataCollector $dataCollector): void {
		$this->dataCollectors[$dataCollector->getName()] = $dataCollector;
	}

	public function loadProfileFromResponse(Response $response): ?IProfile {
		if (!$token = $response->getHeaders()['X-Debug-Token']) {
			return null;
		}

		return $this->loadProfile($token);
	}

	public function loadProfile(string $token): ?IProfile {
		if ($this->storage) {
			return $this->storage->read($token);
		} else {
			return null;
		}
	}

	public function saveProfile(IProfile $profile): bool {
		if ($this->storage) {
			return $this->storage->write($profile);
		} else {
			return false;
		}
	}

	public function collect(Request $request, Response $response): IProfile {
		$profile = new Profile($request->getId());
		$profile->setTime(time());
		$profile->setUrl($request->getRequestUri());
		$profile->setMethod($request->getMethod());
		$profile->setStatusCode($response->getStatus());
		foreach ($this->dataCollectors as $dataCollector) {
			$dataCollector->collect($request, $response, null);

			// We clone for subrequests
			$profile->addCollector(clone $dataCollector);
		}
		return $profile;
	}

	/**
	 * @return array[]
	 */
	public function find(
		?string $url,
		?int $limit,
		?string $method,
		?int $start,
		?int $end,
		string $statusCode = null
	): array {
		return $this->storage->find($url, $limit, $method, $start, $end, $statusCode);
	}

	public function dataProviders(): array {
		return array_keys($this->dataCollectors);
	}

	public function isEnabled(): bool {
		return $this->enabled;
	}

	public function setEnabled(bool $enabled): void {
		$this->enabled = $enabled;
		if ($enabled) {
			$this->add(new HttpDataCollector());
			$this->add(new MemoryDataCollector());
		}
	}

	public function clear(): void {
		$this->storage->purge();
	}
}
