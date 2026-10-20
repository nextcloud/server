<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OCP\Files\File;
use OCP\IConfig;
use OCP\IImage;
use OCP\Image;
use OCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Generates video previews on an ffmpeg-over-ip server
 *
 * @see https://github.com/steelbrain/ffmpeg-over-ip
 */
class MovieRemote extends ProviderV2 {
	/** Timestamps (in seconds) to make attempts to generate a still */
	private const TIME_ATTEMPTS = [5, 1, 0];

	private IConfig $config;
	private LoggerInterface $logger;

	public function __construct(array $options = []) {
		parent::__construct($options);
		$this->config = Server::get(IConfig::class);
		$this->logger = Server::get(LoggerInterface::class);
	}

	#[\Override]
	public function getMimeType(): string {
		return '/video\/.*/';
	}

	#[\Override]
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		$address = $this->config->getSystemValueString('preview_ffmpeg_url', '');
		if ($address === '') {
			$this->logger->error('MovieRemote preview provider is enabled, but no url is configured. Please provide the address of your ffmpeg-over-ip server to the \'preview_ffmpeg_url\' config variable.');
			return null;
		}
		$client = $this->getClient($address, $this->config->getSystemValueString('preview_ffmpeg_key', ''));

		$stream = $this->getSeekableStream($file);
		if ($stream === null) {
			return null;
		}
		// The extension helps ffmpeg to detect the container format
		$extension = preg_replace('/[^a-z0-9]/', '', strtolower($file->getExtension()));
		$input = '/input' . ($extension !== '' ? '.' . $extension : '');
		$files = [$input => ['stream' => $stream, 'size' => (int)$file->getSize()]];

		try {
			$probe = $client->run(FFmpegOverIpClient::PROGRAM_FFPROBE, [...Movie::HDR_PROBE_ARGS, $input], $files);
			$filter = Movie::isHdr($probe['stdout'], $probe['stderr']) ? ['-vf', Movie::HDR_FILTER] : [];

			foreach (self::TIME_ATTEMPTS as $second) {
				$result = $client->run(FFmpegOverIpClient::PROGRAM_FFMPEG, [
					'-ss', (string)$second,
					'-i', $input,
					'-f', 'mjpeg', '-vframes', '1',
					...$filter,
					'pipe:1',
				], $files);

				if ($result['exitCode'] === 0 && $result['stdout'] !== '') {
					$image = new Image();
					$image->loadFromData($result['stdout']);
					if ($image->valid()) {
						$image->scaleDownToFit($maxX, $maxY);
						return $image;
					}
				}
				$this->logger->debug('MovieRemote preview generation attempt failed, file=' . $file->getPath() . ', time=' . $second, [
					'exitCode' => $result['exitCode'],
					'output' => $result['stderr'],
				]);
			}
		} catch (\RuntimeException $e) {
			$this->logger->warning('MovieRemote preview generation failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return null;
		} finally {
			fclose($stream);
		}

		$this->logger->error('MovieRemote preview generation process failed, file=' . $file->getPath());
		return null;
	}

	protected function getClient(string $address, string $secret): FFmpegOverIpClient {
		return new FFmpegOverIpClient($address, $secret);
	}

	/**
	 * Opens the file, copying it to a temporary stream if the storage does not support seeking
	 *
	 * @return resource|null
	 */
	private function getSeekableStream(File $file) {
		$stream = $file->fopen('r');
		if (!is_resource($stream)) {
			return null;
		}
		// Stream wrappers always claim to be seekable, so seeking is tried instead
		try {
			if (stream_get_meta_data($stream)['seekable'] && fseek($stream, 1) === 0 && fseek($stream, 0) === 0) {
				return $stream;
			}
		} catch (\Throwable) {
		}
		fclose($stream);

		$stream = $file->fopen('r');
		if (!is_resource($stream)) {
			return null;
		}
		$copy = fopen('php://temp', 'r+');
		stream_copy_to_stream($stream, $copy);
		fclose($stream);
		rewind($copy);
		return $copy;
	}
}
