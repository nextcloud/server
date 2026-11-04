<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview;

use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\ICertificateManager;
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
	/** Bytes downloaded of videos on storages that cannot seek, matching the first attempt of Movie */
	private const NON_SEEKABLE_SIZE = 10 * 1024 * 1024;

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
	public function isAvailable(FileInfo $file): bool {
		return $this->config->getSystemValueString('preview_ffmpeg_address', '') !== '';
	}

	#[\Override]
	public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
		if (!$this->isAvailable($file)) {
			return null;
		}

		$input = $this->getInput($file);
		if ($input === null) {
			return null;
		}
		$extension = preg_replace('/[^a-z0-9]/', '', strtolower($file->getExtension()));
		$path = '/input' . ($extension !== '' ? '.' . $extension : '');
		$files = [$path => $input];

		try {
			$client = $this->getClient(
				$this->config->getSystemValueString('preview_ffmpeg_address'),
				$this->config->getSystemValueString('preview_ffmpeg_key', ''),
				$this->config->getSystemValueInt('preview_ffmpeg_timeout', 120),
			);
			$hdr = $this->useHdr($client, $path, $files);

			foreach (Movie::TIME_ATTEMPTS as $second) {
				$result = $client->run(FFmpegOverIpClient::PROGRAM_FFMPEG, Movie::getStillArgs($path, $second, $hdr, 'pipe:1'), $files);

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
			fclose($input['stream']);
			$this->cleanTmpFiles();
		}

		$this->logger->error('MovieRemote preview generation process failed, file=' . $file->getPath());
		return null;
	}

	protected function getClient(string $address, string $secret, int $timeout): FFmpegOverIpClient {
		return new FFmpegOverIpClient($address, $secret, $timeout, Server::get(ICertificateManager::class)->getAbsoluteBundlePath());
	}

	/**
	 * @param array<string, array{stream: resource, size: int}> $files
	 */
	private function useHdr(FFmpegOverIpClient $client, string $path, array $files): bool {
		try {
			$probe = $client->run(FFmpegOverIpClient::PROGRAM_FFPROBE, [...Movie::HDR_PROBE_ARGS, $path], $files);
		} catch (\RuntimeException $e) {
			$this->logger->debug('MovieRemote HDR detection failed: ' . $e->getMessage(), ['exception' => $e]);
			return false;
		}
		return Movie::isHdr($probe['stdout'], $probe['stderr']);
	}

	/**
	 * Opens the video, downloading only its beginning to a temporary file if the storage cannot seek
	 *
	 * @return array{stream: resource, size: int}|null
	 */
	private function getInput(File $file): ?array {
		$stream = $file->fopen('r');
		if (!is_resource($stream)) {
			return null;
		}
		// Stream wrappers always claim to be seekable, so seeking is tried instead
		try {
			if (stream_get_meta_data($stream)['seekable'] && fseek($stream, 1) === 0 && fseek($stream, 0) === 0) {
				return ['stream' => $stream, 'size' => (int)$file->getSize()];
			}
		} catch (\Throwable) {
		}
		fclose($stream);

		$localPath = $this->getLocalFile($file, self::NON_SEEKABLE_SIZE);
		$stream = $localPath !== false ? fopen($localPath, 'r') : false;
		if ($stream === false) {
			$this->cleanTmpFiles();
			return null;
		}
		return ['stream' => $stream, 'size' => (int)filesize($localPath)];
	}
}
