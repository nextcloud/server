<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Remote;

use OC\Remote\Api\NotFoundException;
use OCP\Http\Client\IClientService;
use OCP\ICache;
use OCP\Remote\IInstance;
use Override;

/**
 * Provides some basic info about a remote Nextcloud instance
 */
class Instance implements IInstance {
	private string $url;
	private ?array $status = null;

	public function __construct(
		string $url,
		private ICache $cache,
		private IClientService $clientService,
	) {
		$url = str_replace('https://', '', $url);
		$this->url = str_replace('http://', '', $url);
	}

	#[Override]
	public function getUrl(): string {
		return $this->url;
	}

	#[Override]
	public function getFullUrl(): string {
		return $this->getProtocol() . '://' . $this->getUrl();
	}

	#[Override]
	public function getVersion(): string {
		$status = $this->getStatus();
		return $status['version'];
	}

	#[Override]
	public function getProtocol(): string {
		$status = $this->getStatus();
		return $status['protocol'];
	}

	#[Override]
	public function isActive(): bool {
		$status = $this->getStatus();
		return $status['installed'] && !$status['maintenance'];
	}

	private function getStatus(): array {
		if ($this->status) {
			return $this->status;
		}
		$key = 'remote/' . $this->url . '/status';
		$httpsKey = 'remote/' . $this->url . '/https';
		$status = $this->cache->get($key);
		if (!$status) {
			$response = $this->downloadStatus('https://' . $this->getUrl() . '/status.php');
			$protocol = 'https';
			if (!$response) {
				if ($status = $this->cache->get($httpsKey)) {
					throw new \Exception('refusing to connect to remote instance(' . $this->url . ') over http that was previously accessible over https');
				}
				$response = $this->downloadStatus('http://' . $this->getUrl() . '/status.php');
				$protocol = 'http';
			} else {
				$this->cache->set($httpsKey, true, 60 * 60 * 24 * 365);
			}
			$status = json_decode($response, true);
			if ($status) {
				$status['protocol'] = $protocol;
			}
			if ($status) {
				$this->cache->set($key, $status, 5 * 60);
				$this->status = $status;
			} else {
				throw new NotFoundException('Remote server not found at address ' . $this->url);
			}
		}
		return $status;
	}

	private function downloadStatus(string $url): false|string {
		try {
			$request = $this->clientService->newClient()->get($url);
			$content = $request->getBody();

			// IResponse.getBody responds with null|resource if returning a stream response was requested.
			// As that's not the case here, we can just ignore the psalm warning by adding an assertion.
			assert(is_string($content));

			return $content;
		} catch (\Exception) {
			return false;
		}
	}
}
