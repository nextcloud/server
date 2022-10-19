<?php

declare(strict_types = 1);

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
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\IDataCollector;
use OCP\Profiler\IProfiler;
use OCP\Profiler\IProfile;
use OC\SystemConfig;

class Profiler implements IProfiler {
	/** @var array<string, IDataCollector> */
	private array $dataCollectors = [];

	private ?FileProfilerStorage $storage = null;

	private bool $enabled = false;

	public function __construct(SystemConfig $config) {
		$this->enabled = $config->getValue('profiler', false);
		if ($this->enabled) {
			$this->storage = new FileProfilerStorage($config->getValue('datadirectory', \OC::$SERVERROOT . '/data') . '/profiler');
		}
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
		return $this->storage->read($token);
	}

	public function saveProfile(IProfile $profile): bool {
		return $this->storage->write($profile);
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
	public function find(?string $url, ?int $limit, ?string $method, ?int $start, ?int $end,
						 string $statusCode = null): array {
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
	}

	public function clear(): void {
		$this->storage->purge();
	}
}
