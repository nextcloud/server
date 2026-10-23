<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OCP\Files\File;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IImage;
use OCP\Image;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Generates video previews by sending the file to an external ffmpeg service
 */
class MovieRemote extends ProviderV2 {
	/** Timestamps (in seconds) the service tries in order to grab a still */
	private const TIME_ATTEMPTS = [5, 1, 0];

	private IConfig $config;
	private IClientService $service;
	private LoggerInterface $logger;

	public function __construct(array $options = []) {
		parent::__construct($options);
		$this->config = Server::get(IConfig::class);
		$this->service = Server::get(IClientService::class);
		$this->logger = Server::get(LoggerInterface::class);
	}

	#[\Override]
	public function getMimeType(): string {
		return '/video\/.*/';
	}

	#[\Override]
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$serviceUrl = $this->config->getSystemValueString('preview_ffmpeg_url', '');
		if ($serviceUrl === '') {
			$this->logger->error('MovieRemote preview provider is enabled, but no url is configured. Please provide the url of your ffmpeg service to the \'preview_ffmpeg_url\' config variable.');
			return null;
		}
		$serviceUrl = rtrim($serviceUrl, '/');

		$stream = $file->fopen('r');
		if (!$stream || !is_resource($stream) || feof($stream)) {
			return null;
		}

		$headers = [
			'Content-Type' => $file->getMimeType(),
			'Content-Length' => (string)$file->getSize(),
		];
		$key = $this->config->getSystemValueString('preview_ffmpeg_key', '');
		if ($key !== '') {
			$headers['Authorization'] = 'Bearer ' . $key;
		}

		try {
			$response = $this->service->newClient()->post(
				$serviceUrl . '/thumbnail', [
					'query' => [
						'width' => $maxX,
						'height' => $maxY,
						'time' => implode(',', self::TIME_ATTEMPTS),
					],
					'stream' => true,
					'headers' => $headers,
					'body' => $stream,
					'nextcloud' => ['allow_local_address' => true],
					'timeout' => 120,
					'connect_timeout' => 3,
				]);
		} catch (\Throwable $e) {
			$this->logger->info('MovieRemote preview generation failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return null;
		} finally {
			if (is_resource($stream)) {
				fclose($stream);
			}
		}

		if ($response->getStatusCode() !== 200) {
			$this->logger->info('MovieRemote preview generation failed with status code ' . $response->getStatusCode() . ' for ' . $file->getPath());
			return null;
		}

		$image = new Image();
		$image->loadFromFileHandle($response->getBody());
		if (!$image->valid()) {
			return null;
		}
		$image->scaleDownToFit($maxX, $maxY);
		return $image;
	}
}
