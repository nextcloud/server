<?php
/**
 * @copyright Copyright (c) 2020, Nextcloud, GmbH.
 *
 * @author Vincent Petry <vincent@nextcloud.com>
 * @author Carl Schwan <carl@carlschwan.eu>
 *
 * @license AGPL-3.0-or-later
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Preview;

use OCP\Files\File;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IImage;
use OCP\Image;

use OC\StreamImage;
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
		$this->config = \OC::$server->get(IConfig::class);
		$this->service = \OC::$server->get(IClientService::class);
		$this->logger = \OC::$server->get(LoggerInterface::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string {
		return self::supportedMimeTypes();
	}

	public static function supportedMimeTypes(): string {
		return '/(image\/(bmp|x-bitmap|png|jpeg|gif|heic|heif|svg\+xml|tiff|webp)|application\/(pdf|illustrator))/';
	}

	public function getCroppedThumbnail(File $file, int $maxX, int $maxY, bool $crop): ?IImage {
		$maxSizeForImages = $this->config->getSystemValue('preview_max_filesize_image', 50);

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

		$quality = $this->config->getAppValue('preview', 'jpeg_quality', '80');

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
			$response = $httpClient->post(
				$imaginaryUrl . '/pipeline', [
					'query' => ['operations' => json_encode($operations)],
					'stream' => true,
					'content-type' => $file->getMimeType(),
					'body' => $stream,
					'nextcloud' => ['allow_local_address' => true],
					'timeout' => 120,
					'connect_timeout' => 3,
				]);
		} catch (\Exception $e) {
			$this->logger->error('Imaginary preview generation failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return null;
		}

		if ($response->getStatusCode() !== 200) {
			$this->logger->error('Imaginary preview generation failed: ' . json_decode($response->getBody())['message']);
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
