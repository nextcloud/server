<?php

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OC\StreamImage;
use OCP\Files\File;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IImage;
use OCP\Image;

use OCP\Server;
use Psr\Log\LoggerInterface;

class Imaginary extends ProviderV2 {
	/** @var IConfig */
	private $config;

	/** @var IClientService */
	private $service;

	/** @var LoggerInterface */
	private $logger;

	public function __construct(array $config) {
		parent::__construct($config);
		$this->config = Server::get(IConfig::class);
		$this->service = Server::get(IClientService::class);
		$this->logger = Server::get(LoggerInterface::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return self::supportedMimeTypes();
	}

	public static function supportedMimeTypes(): string {
		return '/(image\/(bmp|x-bitmap|png|jpeg|gif|heic|heif|svg\+xml|tiff|webp)|application\/illustrator)/';
	}

	public function getCroppedThumbnail(File $file, int $maxX, int $maxY, bool $crop): ?IImage {
		$maxSizeForImages = $this->config->getSystemValueInt('preview_max_filesize_image', 50);

		$size = $file->getSize();

		if ($maxSizeForImages !== -1 && $size > ($maxSizeForImages * 1024 * 1024)) {
			return null;
		}

		$imaginaryUrl = $this->config->getSystemValueString('preview_imaginary_url', 'invalid');
		if ($imaginaryUrl === 'invalid') {
			$this->logger->error('Imaginary preview provider is enabled, but no url is configured. Please provide the url of your imaginary server to the \'preview_imaginary_url\' config variable.');
			return null;
		}
		$imaginaryUrl = rtrim($imaginaryUrl, '/');

		// Object store
		$stream = $file->fopen('r');
		if (!$stream || !is_resource($stream) || feof($stream)) {
			return null;
		}

		$httpClient = $this->service->newClient();

		$convert = false;
		$autorotate = true;

		switch ($file->getMimeType()) {
			case 'image/heic':
				// Autorotate seems to be broken for Heic so disable for that
				$autorotate = false;
				$mimeType = 'jpeg';
				break;
			case 'image/gif':
			case 'image/png':
				$mimeType = 'png';
				break;
			case 'image/svg+xml':
			case 'application/pdf':
			case 'application/illustrator':
				$convert = true;
				// Converted files do not need to be autorotated
				$autorotate = false;
				$mimeType = 'png';
				break;
			default:
				$mimeType = 'jpeg';
		}

		$preview_format = $this->config->getSystemValueString('preview_format', 'jpeg');

		switch ($preview_format) { // Change the format to the correct one
			case 'webp':
				$mimeType = 'webp';
				break;
			default:
		}

		$operations = [];

		if ($convert) {
			$operations[] = [
				'operation' => 'convert',
				'params' => [
					'type' => $mimeType,
				]
			];
		} elseif ($autorotate) {
			$operations[] = [
				'operation' => 'autorotate',
			];
		}

		switch ($mimeType) {
			case 'jpeg':
				$quality = $this->config->getAppValue('preview', 'jpeg_quality', '80');
				break;
			case 'webp':
				$quality = $this->config->getAppValue('preview', 'webp_quality', '80');
				break;
			default:
				$quality = $this->config->getAppValue('preview', 'jpeg_quality', '80');
		}

		$operations[] = [
			'operation' => ($crop ? 'smartcrop' : 'fit'),
			'params' => [
				'width' => $maxX,
				'height' => $maxY,
				'stripmeta' => 'true',
				'type' => $mimeType,
				'norotation' => 'true',
				'quality' => $quality,
			]
		];

		try {
			$imaginaryKey = $this->config->getSystemValueString('preview_imaginary_key', '');
			$response = $httpClient->post(
				$imaginaryUrl . '/pipeline', [
					'query' => ['operations' => json_encode($operations), 'key' => $imaginaryKey],
					'stream' => true,
					'content-type' => $file->getMimeType(),
					'body' => $stream,
					'nextcloud' => ['allow_local_address' => true],
					'timeout' => 120,
					'connect_timeout' => 3,
				]);
		} catch (\Throwable $e) {
			$this->logger->info('Imaginary preview generation failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return null;
		}

		if ($response->getStatusCode() !== 200) {
			$this->logger->info('Imaginary preview generation failed: ' . json_decode($response->getBody())['message']);
			return null;
		}

		// This is not optimal but previews are distorted if the wrong width and height values are
		// used. Both dimension headers are only sent when passing the option "-return-size" to
		// Imaginary.
		if ($response->getHeader('Image-Width') && $response->getHeader('Image-Height')) {
			$image = new StreamImage(
				$response->getBody(),
				$response->getHeader('Content-Type'),
				(int)$response->getHeader('Image-Width'),
				(int)$response->getHeader('Image-Height'),
			);
		} else {
			$image = new Image();
			$image->loadFromFileHandle($response->getBody());
		}

		return $image->valid() ? $image : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		return $this->getCroppedThumbnail($file, $maxX, $maxY, false);
	}
}
